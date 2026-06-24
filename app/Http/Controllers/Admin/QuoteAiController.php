<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRateCard;
use App\Models\Quote;
use App\Models\QuoteAiGeneration;
use App\Services\QuoteAiPricingContextBuilder;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class QuoteAiController extends Controller
{
    public function compile(Request $request, Quote $quote, QuoteAiPricingContextBuilder $contextBuilder)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'pricing_hint' => ['nullable', 'string', 'max:10000'],
        ]);

        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.estimate_model', config('services.openai.model', 'gpt-4o'));

        if (! $apiKey) {
            return redirect()
                ->route('admin.quotes.pricing', $quote)
                ->withErrors([
                    'ai' => 'OpenAI API key is missing. Add OPENAI_API_KEY to your .env file.',
                ]);
        }

        $quote->load([
            'customer.contacts',
            'notes.creator',
            'lineItems',
            'files.uploadedBy',
        ]);

        $inputPayload = $contextBuilder->build($quote, $validated['pricing_hint'] ?? null);

        $generation = QuoteAiGeneration::create([
            'quote_id' => $quote->id,
            'created_by_user_id' => auth()->id(),
            'provider' => 'openai',
            'model' => $model,
            'input_payload' => json_encode($inputPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'status' => 'pending',
        ]);

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(120)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->systemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode($inputPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'sitedesk_ai_estimate_draft',
                            'strict' => true,
                            'schema' => $this->jsonSchema(),
                        ],
                    ],
                    'temperature' => 0.2,
                ])
                ->throw()
                ->json();

            $content = $response['choices'][0]['message']['content'] ?? null;

            if (! $content) {
                throw new RuntimeException('OpenAI returned an empty response.');
            }

            $decoded = json_decode($content, true);

            if (! is_array($decoded)) {
                throw new RuntimeException('OpenAI response was not valid JSON.');
            }

            $this->storeDraft($quote, $generation, $decoded);

            $generation->update([
                'status' => 'completed',
                'output_payload' => json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'error_message' => null,
            ]);

            return redirect()
                ->route('admin.quotes.pricing', $quote)
                ->with('status', 'AI estimate draft created. Review, edit and accept the draft items before applying them to the quote.');
        } catch (RequestException $exception) {
            $message = $exception->response?->json('error.message') ?: $exception->getMessage();

            $generation->update([
                'status' => 'failed',
                'error_message' => $message,
            ]);

            return redirect()
                ->route('admin.quotes.pricing', $quote)
                ->withErrors([
                    'ai' => 'OpenAI request failed: ' . $message,
                ]);
        } catch (\Throwable $exception) {
            $generation->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('admin.quotes.pricing', $quote)
                ->withErrors([
                    'ai' => 'AI compile failed: ' . $exception->getMessage(),
                ]);
        }
    }

    private function storeDraft(Quote $quote, QuoteAiGeneration $generation, array $decoded)
    {
        $rateCard = PricingRateCard::activeOrCreateDefault();
        $vatPercent = (float) $rateCard->vat_percent;

        return DB::transaction(function () use ($quote, $generation, $decoded, $vatPercent) {
            $draft = $quote->aiDrafts()->create([
                'quote_ai_generation_id' => $generation->id,
                'status' => 'pending_review',
                'detected_job_type' => $decoded['detected_project_type'] ?? null,
                'selected_template_code' => $decoded['selected_template_code'] ?? null,
                'overall_confidence' => $decoded['overall_confidence'] ?? 'medium',
                'pricing_basis' => 'ai_estimate',
                'missing_information' => $decoded['missing_information'] ?? [],
                'warnings' => $decoded['pricing_warnings'] ?? [],
                'assumptions' => $decoded['assumptions'] ?? [],
                'exclusions' => $decoded['exclusions'] ?? [],
                'internal_reasoning' => $decoded['internal_reasoning'] ?? null,
                'customer_message' => $decoded['customer_pack']['customer_message'] ?? null,
                'scope_of_works' => $decoded['customer_pack']['scope_of_works'] ?? null,
                'timeline' => $decoded['customer_pack']['estimated_timeline'] ?? null,
                'terms' => $decoded['customer_pack']['terms'] ?? null,
            ]);

            foreach (($decoded['estimate_items'] ?? []) as $item) {
                $quantity = max((float) ($item['quantity'] ?? 1), 0.01);

                $lowPence = $this->poundsToPence($item['low_estimate_ex_vat'] ?? 0);
                $likelyPence = $this->poundsToPence($item['likely_estimate_ex_vat'] ?? 0);
                $highPence = $this->poundsToPence($item['high_estimate_ex_vat'] ?? 0);

                if ($likelyPence <= 0) {
                    continue;
                }

                $unitAmountPence = (int) round($likelyPence / $quantity);
                $vatPence = $this->percentOf($likelyPence, $vatPercent);

                $draft->items()->create([
                    'quote_id' => $quote->id,
                    'pricing_rate_item_id' => null,
                    'category' => $item['category'] ?? 'other_works',
                    'rate_item_code' => null,
                    'clean_customer_description' => $this->cleanCustomerDescription($item['customer_description'] ?? $item['description'] ?? 'Works allowance'),
                    'internal_reasoning' => $item['internal_reasoning'] ?? null,
                    'estimate_explanation' => $item['estimate_explanation'] ?? null,
                    'quantity' => $quantity,
                    'unit' => $item['unit'] ?? 'item',
                    'base_unit_cost_pence' => $unitAmountPence,
                    'base_total_pence' => $likelyPence,
                    'markup_percent' => 0,
                    'contingency_percent' => 0,
                    'vat_percent' => $vatPercent,
                    'contingency_pence' => 0,
                    'markup_pence' => 0,
                    'subtotal_pence' => $likelyPence,
                    'vat_pence' => $vatPence,
                    'total_pence' => $likelyPence + $vatPence,
                    'low_total_pence' => $lowPence,
                    'likely_total_pence' => $likelyPence,
                    'high_total_pence' => $highPence,
                    'confidence' => $this->normaliseConfidence($item['confidence'] ?? 'medium'),
                    'pricing_source' => 'ai_estimate',
                    'pricing_basis' => $item['pricing_basis'] ?? 'ai_estimate',
                    'evidence' => $item['evidence'] ?? [],
                    'warnings' => $item['warnings'] ?? [],
                    'status' => 'pending',
                ]);
            }

            return $draft;
        });
    }

    private function cleanCustomerDescription(string $description): string
    {
        $description = trim(preg_replace('/\s+/', ' ', $description));
        $description = preg_replace('/\b(ai|chatgpt|model|reasoning|confidence|low estimate|high estimate)\b/i', '', $description);
        $description = trim(preg_replace('/\s+/', ' ', $description));

        return mb_substr($description ?: 'Works allowance', 0, 255);
    }

    private function poundsToPence(mixed $value): int
    {
        return max((int) round((float) $value * 100), 0);
    }

    private function percentOf(int $amountPence, float $percent): int
    {
        return (int) round($amountPence * ($percent / 100));
    }

    private function normaliseConfidence(string $confidence): string
    {
        return in_array($confidence, ['low', 'medium', 'high'], true) ? $confidence : 'medium';
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a senior UK building-estimating assistant working inside SiteDesk.

Your job is to create a draft estimate that a human must review.

You may:
- Estimate prices using survey notes, quote context, user pricing hints, job templates and pricing guidance.
- Use professional UK building-estimating judgement when guidance is incomplete.
- Guess what work is likely to be required, but you must show uncertainty.
- Use provisional allowances where specification is unclear.
- Produce customer-facing wording for the quote pack.

You must:
- Return low, likely and high ex-VAT estimates for every estimate item.
- State pricing_basis for every item.
- State confidence for every item.
- Add warnings to low-confidence or provisional items.
- Flag missing information clearly.
- Keep internal reasoning separate from customer descriptions.
- Use UK English.

You must not:
- Pretend uncertain prices are fixed.
- Hide missing information.
- Put internal reasoning, AI notes or uncertainty into customer_description.
- Mention AI, ChatGPT or language models in customer-facing wording.
- Include VAT in line-item estimate values. Estimate item values must be ex-VAT.
PROMPT;
    }

    private function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'detected_project_type' => ['type' => 'string'],
                'selected_template_code' => ['type' => 'string'],
                'overall_confidence' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                'internal_reasoning' => ['type' => 'string'],
                'estimate_items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'category' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'customer_description' => ['type' => 'string'],
                            'quantity' => ['type' => 'number'],
                            'unit' => ['type' => 'string'],
                            'low_estimate_ex_vat' => ['type' => 'number'],
                            'likely_estimate_ex_vat' => ['type' => 'number'],
                            'high_estimate_ex_vat' => ['type' => 'number'],
                            'pricing_basis' => [
                                'type' => 'string',
                                'enum' => [
                                    'pricing_guidance',
                                    'project_template',
                                    'historical_guidance',
                                    'user_hint',
                                    'provisional_allowance',
                                    'professional_estimate',
                                    'market_assumption',
                                ],
                            ],
                            'confidence' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                            'estimate_explanation' => ['type' => 'string'],
                            'internal_reasoning' => ['type' => 'string'],
                            'evidence' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'warnings' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                        'required' => [
                            'category',
                            'description',
                            'customer_description',
                            'quantity',
                            'unit',
                            'low_estimate_ex_vat',
                            'likely_estimate_ex_vat',
                            'high_estimate_ex_vat',
                            'pricing_basis',
                            'confidence',
                            'estimate_explanation',
                            'internal_reasoning',
                            'evidence',
                            'warnings',
                        ],
                    ],
                ],
                'missing_information' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'field' => ['type' => 'string'],
                            'severity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                            'question' => ['type' => 'string'],
                        ],
                        'required' => ['field', 'severity', 'question'],
                    ],
                ],
                'pricing_warnings' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'assumptions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'exclusions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'customer_pack' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'customer_message' => ['type' => 'string'],
                        'scope_of_works' => ['type' => 'string'],
                        'estimated_timeline' => ['type' => 'string'],
                        'terms' => ['type' => 'string'],
                    ],
                    'required' => ['customer_message', 'scope_of_works', 'estimated_timeline', 'terms'],
                ],
            ],
            'required' => [
                'detected_project_type',
                'selected_template_code',
                'overall_confidence',
                'internal_reasoning',
                'estimate_items',
                'missing_information',
                'pricing_warnings',
                'assumptions',
                'exclusions',
                'customer_pack',
            ],
        ];
    }
}