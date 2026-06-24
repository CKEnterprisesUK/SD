<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRateCard;
use App\Models\PricingRateItem;
use App\Models\Quote;
use App\Models\QuoteAiGeneration;
use App\Services\QuoteAiPricingContextBuilder;
use App\Services\QuotePricingCalculator;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class QuoteAiController extends Controller
{
    public function compile(Request $request, Quote $quote, QuoteAiPricingContextBuilder $contextBuilder, QuotePricingCalculator $calculator)
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
                            'name' => 'sitedesk_quote_pricing_draft',
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

            $draft = $this->storeDraft($quote, $generation, $decoded, $calculator);

            $generation->update([
                'status' => 'completed',
                'output_payload' => json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'error_message' => null,
            ]);

            return redirect()
                ->route('admin.quotes.pricing', $quote)
                ->with('status', 'AI pricing draft created. Review, edit and accept the draft items before applying them to the quote.');
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

    private function storeDraft(Quote $quote, QuoteAiGeneration $generation, array $decoded, QuotePricingCalculator $calculator)
    {
        $rateCard = PricingRateCard::activeOrCreateDefault();
        $rateItems = PricingRateItem::where('is_active', true)->get()->keyBy('code');
        $draftWarnings = $decoded['pricing_warnings'] ?? [];

        return DB::transaction(function () use ($quote, $generation, $decoded, $calculator, $rateCard, $rateItems, &$draftWarnings) {
            $draft = $quote->aiDrafts()->create([
                'quote_ai_generation_id' => $generation->id,
                'status' => 'pending_review',
                'detected_job_type' => $decoded['detected_job_type'] ?? null,
                'selected_template_code' => $decoded['selected_template_code'] ?? null,
                'overall_confidence' => $decoded['overall_confidence'] ?? 'medium',
                'pricing_basis' => $decoded['pricing_basis'] ?? null,
                'missing_information' => $decoded['missing_information'] ?? [],
                'warnings' => $draftWarnings,
                'assumptions' => $decoded['assumptions'] ?? [],
                'exclusions' => $decoded['exclusions'] ?? [],
                'internal_reasoning' => $decoded['internal_reasoning'] ?? null,
                'customer_message' => $decoded['customer_pack']['customer_message'] ?? null,
                'scope_of_works' => $decoded['customer_pack']['scope_of_works'] ?? null,
                'timeline' => $decoded['customer_pack']['estimated_timeline'] ?? null,
                'terms' => $decoded['customer_pack']['terms'] ?? null,
            ]);

            foreach (($decoded['pricing_items'] ?? []) as $item) {
                $code = $item['rate_item_code'] ?? null;
                $rateItem = $code ? $rateItems->get($code) : null;

                if (! $rateItem) {
                    $draftWarnings[] = 'AI selected an unknown or inactive rate-card code: ' . ($code ?: 'blank') . '. The item was not priced.';
                    continue;
                }

                $quantity = (float) ($item['quantity'] ?? 0);

                if ($quantity <= 0) {
                    $draftWarnings[] = 'AI returned a zero or negative quantity for ' . $code . '. The item was not priced.';
                    continue;
                }

                $confidence = in_array(($item['confidence'] ?? 'medium'), ['low', 'medium', 'high'], true)
                    ? $item['confidence']
                    : 'medium';

                $calculated = $calculator->calculate($rateItem, $quantity, $rateCard, $confidence);

                $draft->items()->create(array_merge($calculated, [
                    'quote_id' => $quote->id,
                    'pricing_rate_item_id' => $rateItem->id,
                    'category' => $rateItem->category,
                    'rate_item_code' => $rateItem->code,
                    'clean_customer_description' => $this->cleanCustomerDescription($item['customer_description'] ?? $rateItem->customer_description ?? $rateItem->name),
                    'internal_reasoning' => $item['internal_reasoning'] ?? null,
                    'quantity' => $quantity,
                    'unit' => $rateItem->unit,
                    'confidence' => $confidence,
                    'pricing_source' => 'rate_card',
                    'evidence' => $item['evidence'] ?? [],
                    'warnings' => $item['warnings'] ?? [],
                    'status' => 'pending',
                ]));
            }

            $draft->update(['warnings' => $draftWarnings]);

            return $draft;
        });
    }

    private function cleanCustomerDescription(string $description): string
    {
        $description = trim(preg_replace('/\s+/', ' ', $description));
        $description = preg_replace('/\b(ai|chatgpt|model|reasoning|confidence)\b/i', '', $description);
        $description = trim(preg_replace('/\s+/', ' ', $description));

        return mb_substr($description ?: 'Works allowance', 0, 255);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a senior UK building-estimating assistant working inside SiteDesk.

Your job is to do the estimating thinking, not the final pricing maths.

You must:
- Read the quote, survey notes, file captions, existing line items, pricing hints, rate-card items and job templates.
- Identify the likely job type and closest job template.
- Select the best matching rate-card items using only supplied rate_item_code values.
- Estimate realistic quantities from the supplied context.
- Use existing pricing hints to improve quantities, risk and assumptions.
- Flag unclear measurements, access, specification, materials, site risks and exclusions.
- Produce clean customer-facing wording.
- Keep internal reasoning separate from customer-facing descriptions.

You must not:
- Invent unit prices.
- Calculate final totals, VAT, markup or margin.
- Use rate-card codes not supplied in the prompt.
- Put warnings, AI notes, uncertainty or reasoning into customer_description.
- Claim that hidden defects, asbestos, building control, planning, party wall, drainage, utilities or structural matters are resolved unless explicitly stated.
- Mention AI, ChatGPT or language models in customer-facing wording.

Quantity rules:
- quantity must be greater than zero.
- If information is missing, estimate cautiously and flag the missing information.
- Low-confidence items must have warnings.
- Use UK English.
PROMPT;
    }

    private function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'detected_job_type' => ['type' => 'string'],
                'selected_template_code' => ['type' => 'string'],
                'overall_confidence' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                'pricing_basis' => ['type' => 'string'],
                'internal_reasoning' => ['type' => 'string'],
                'pricing_items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'rate_item_code' => ['type' => 'string'],
                            'customer_description' => ['type' => 'string'],
                            'quantity' => ['type' => 'number'],
                            'unit' => ['type' => 'string'],
                            'confidence' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                            'pricing_source' => ['type' => 'string', 'enum' => ['rate_card']],
                            'evidence' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'warnings' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'internal_reasoning' => ['type' => 'string'],
                        ],
                        'required' => [
                            'rate_item_code',
                            'customer_description',
                            'quantity',
                            'unit',
                            'confidence',
                            'pricing_source',
                            'evidence',
                            'warnings',
                            'internal_reasoning',
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
                'detected_job_type',
                'selected_template_code',
                'overall_confidence',
                'pricing_basis',
                'internal_reasoning',
                'pricing_items',
                'missing_information',
                'pricing_warnings',
                'assumptions',
                'exclusions',
                'customer_pack',
            ],
        ];
    }
}
