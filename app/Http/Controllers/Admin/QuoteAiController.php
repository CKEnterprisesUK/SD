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
    /**
     * Backwards-compatible method for the old route.
     * Existing route admin.quotes.compile-ai can safely point here.
     */
    public function compile(Request $request, Quote $quote, QuoteAiPricingContextBuilder $contextBuilder)
    {
        return $this->generateEstimate($request, $quote, $contextBuilder);
    }

    /**
     * Pricing-only AI call.
     */
    public function generateEstimate(Request $request, Quote $quote, QuoteAiPricingContextBuilder $contextBuilder)
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
            $decoded = $this->callOpenAiJson(
                apiKey: $apiKey,
                model: $model,
                systemPrompt: $this->estimateSystemPrompt(),
                inputPayload: $inputPayload,
                schemaName: 'sitedesk_ai_estimate_draft',
                schema: $this->estimateJsonSchema()
            );

            $this->storeEstimateDraft($quote, $generation, $decoded);

            $generation->update([
                'status' => 'completed',
                'output_payload' => json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'error_message' => null,
            ]);

            return redirect()
                ->route('admin.quotes.pricing', $quote)
                ->with('status', 'AI estimate created. Review the items before applying them to the quote.');
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
                    'ai' => 'AI estimate failed: ' . $exception->getMessage(),
                ]);
        }
    }

    /**
     * Customer-pack wording-only AI call.
     */
    public function generateWording(Request $request, Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'wording_hint' => ['nullable', 'string', 'max:10000'],
        ]);

        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.wording_model', config('services.openai.model', 'gpt-4o'));

        if (! $apiKey) {
            return redirect()
                ->route('admin.quotes.pack', $quote)
                ->withErrors([
                    'ai' => 'OpenAI API key is missing. Add OPENAI_API_KEY to your .env file.',
                ]);
        }

        $quote->load([
            'customer.contacts',
            'notes.creator',
            'lineItems',
            'files.uploadedBy',
            'aiDrafts.items',
        ]);

        $inputPayload = $this->buildWordingContext($quote, $validated['wording_hint'] ?? null);

        $generation = QuoteAiGeneration::create([
            'quote_id' => $quote->id,
            'created_by_user_id' => auth()->id(),
            'provider' => 'openai',
            'model' => $model,
            'input_payload' => json_encode($inputPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'status' => 'pending',
        ]);

        try {
            $decoded = $this->callOpenAiJson(
                apiKey: $apiKey,
                model: $model,
                systemPrompt: $this->wordingSystemPrompt(),
                inputPayload: $inputPayload,
                schemaName: 'sitedesk_customer_pack_wording',
                schema: $this->wordingJsonSchema()
            );

            $this->storeWording($quote, $decoded);

            $generation->update([
                'status' => 'completed',
                'output_payload' => json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'error_message' => null,
            ]);

            return redirect()
                ->route('admin.quotes.pack', $quote)
                ->with('status', 'Customer pack wording generated. Review it before downloading the PDF.');
        } catch (RequestException $exception) {
            $message = $exception->response?->json('error.message') ?: $exception->getMessage();

            $generation->update([
                'status' => 'failed',
                'error_message' => $message,
            ]);

            return redirect()
                ->route('admin.quotes.pack', $quote)
                ->withErrors([
                    'ai' => 'OpenAI request failed: ' . $message,
                ]);
        } catch (\Throwable $exception) {
            $generation->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('admin.quotes.pack', $quote)
                ->withErrors([
                    'ai' => 'Customer wording failed: ' . $exception->getMessage(),
                ]);
        }
    }

    private function callOpenAiJson(
        string $apiKey,
        string $model,
        string $systemPrompt,
        array $inputPayload,
        string $schemaName,
        array $schema
    ): array {
        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(120)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode($inputPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                    ],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => $schemaName,
                        'strict' => true,
                        'schema' => $schema,
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

        return $decoded;
    }

    private function storeEstimateDraft(Quote $quote, QuoteAiGeneration $generation, array $decoded)
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
                'customer_message' => null,
                'scope_of_works' => null,
                'timeline' => null,
                'terms' => null,
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
                    'pricing_basis' => $item['pricing_basis'] ?? 'professional_estimate',
                    'evidence' => $item['evidence'] ?? [],
                    'warnings' => $item['warnings'] ?? [],
                    'status' => 'pending',
                ]);
            }

            return $draft;
        });
    }

    private function buildWordingContext(Quote $quote, ?string $wordingHint): array
    {
        $selectedPhotos = $quote->files
            ->filter(fn ($file) => (bool) ($file->include_in_quote_pack ?? false))
            ->sortBy(fn ($file) => (int) ($file->quote_pack_sort_order ?? 0))
            ->values();

        $firstSurveyNote = $quote->notes
            ->sortBy('created_at')
            ->first();

        return [
            'instruction' => 'Write the customer-facing project requirements and scope section for a UK building quote. Do not produce prices.',
            'quote' => [
                'quote_number' => $quote->quote_number,
                'title' => $quote->title,
                'status' => $quote->status,
                'site_address' => $quote->site_address,
                'summary' => $quote->summary,
                'internal_notes' => $quote->internal_notes,
                'wording_hint_from_user' => $wordingHint,
            ],
            'customer' => [
                'name' => $quote->customer?->display_name,
                'address' => $quote->customer?->address,
                'contacts' => $quote->customer?->contacts?->map(fn ($contact) => [
                    'name' => $contact->name,
                    'role' => $contact->role,
                    'is_primary' => $contact->is_primary,
                ])->values()->all() ?? [],
            ],
            'survey' => [
                'first_note_date' => $firstSurveyNote?->created_at?->format('j F Y'),
                'first_note_created_by' => $firstSurveyNote?->creator?->name,
                'notes' => $quote->notes->sortBy('created_at')->map(fn ($note) => [
                    'date' => $note->created_at?->format('j F Y'),
                    'time' => $note->created_at?->format('H:i'),
                    'created_by' => $note->creator?->name,
                    'type' => $note->type,
                    'room_or_area' => $note->room_or_area,
                    'body' => $note->body,
                ])->values()->all(),
            ],
            'selected_photos' => $selectedPhotos->map(fn ($file) => [
                'id' => $file->id,
                'room_or_area' => $file->room_or_area,
                'caption' => $file->quote_pack_caption ?: $file->caption,
                'original_name' => $file->original_name,
            ])->values()->all(),
            'all_photos' => $quote->files->map(fn ($file) => [
                'id' => $file->id,
                'room_or_area' => $file->room_or_area,
                'caption' => $file->caption,
                'include_in_quote_pack' => (bool) ($file->include_in_quote_pack ?? false),
            ])->values()->all(),
            'accepted_quote_items' => $quote->lineItems->map(fn ($lineItem) => [
                'type' => $lineItem->type,
                'description' => $lineItem->description,
                'quantity' => $lineItem->quantity,
                'unit' => $lineItem->unit,
                'total_ex_vat_pounds' => round($lineItem->total_pence / 100, 2),
            ])->values()->all(),
            'existing_customer_pack_fields' => [
                'final_customer_message' => $quote->final_customer_message,
                'final_site_visit_summary' => $quote->final_site_visit_summary ?? null,
                'final_existing_setup' => $quote->final_existing_setup ?? null,
                'final_measurements_summary' => $quote->final_measurements_summary ?? null,
                'final_customer_requirements' => $quote->final_customer_requirements ?? null,
                'final_preferences_assumptions' => $quote->final_preferences_assumptions ?? null,
                'final_scope' => $quote->final_scope,
                'final_timeline' => $quote->final_timeline,
                'final_assumptions' => $quote->final_assumptions,
                'final_exclusions' => $quote->final_exclusions,
                'final_terms' => $quote->final_terms,
            ],
            'rules' => [
                'Do not invent survey dates. Use the supplied date only if present.',
                'Do not invent contractor names. Use the supplied creator/assigned person only if present.',
                'Do not invent measurements. If measurements are missing, say they are to be confirmed.',
                'Do not produce prices.',
                'Do not mention AI, ChatGPT or language models.',
                'Write as customer-facing quote pack wording.',
            ],
        ];
    }

    private function storeWording(Quote $quote, array $decoded): void
    {
        $quote->forceFill([
            'final_customer_message' => $decoded['customer_message'] ?? $quote->final_customer_message,
            'final_site_visit_summary' => $decoded['site_visit_summary'] ?? null,
            'final_existing_setup' => $decoded['existing_setup'] ?? null,
            'final_measurements_summary' => $decoded['measurements_summary'] ?? null,
            'final_customer_requirements' => $decoded['customer_requirements'] ?? null,
            'final_preferences_assumptions' => $decoded['preferences_and_assumptions'] ?? null,
            'final_scope' => $decoded['proposed_scope'] ?? $quote->final_scope,
            'final_assumptions' => implode("\n", $decoded['assumptions'] ?? []),
            'final_exclusions' => implode("\n", $decoded['exclusions'] ?? []),
            'final_timeline' => $decoded['timeline'] ?? $quote->final_timeline,
            'final_terms' => $decoded['terms'] ?? $quote->final_terms,
            'status' => 'ai_compiled',
        ])->save();

        foreach (($decoded['photo_caption_suggestions'] ?? []) as $suggestion) {
            $fileId = (int) ($suggestion['quote_file_id'] ?? 0);

            if ($fileId <= 0) {
                continue;
            }

            $file = $quote->files->firstWhere('id', $fileId);

            if (! $file) {
                continue;
            }

            $file->forceFill([
                'quote_pack_caption' => $suggestion['caption'] ?? $file->quote_pack_caption,
            ])->save();
        }
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

    private function estimateSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a senior UK building-estimating assistant working inside SiteDesk.

Create a draft estimate that a human must review.

You may estimate prices using:
- survey notes
- quote context
- user pricing hints
- job templates
- pricing guidance
- professional UK building-estimating judgement

You must:
- Return low, likely and high ex-VAT estimates for every estimate item.
- State pricing_basis for every item.
- State confidence for every item.
- Add warnings to low-confidence or provisional items.
- Flag missing information clearly.
- Keep internal reasoning separate from customer descriptions.
- Use UK English.

You must not:
- Produce customer pack wording.
- Pretend uncertain prices are fixed.
- Hide missing information.
- Put internal reasoning into customer_description.
- Mention AI, ChatGPT or language models in customer-facing text.
- Include VAT in line-item estimate values.
PROMPT;
    }

    private function wordingSystemPrompt(): string
    {
        return <<<'PROMPT'
You are writing the customer-facing requirements and scope section for a UK building quote.

Your job is to turn survey notes into clear customer-facing wording.

Write about:
- the site visit or survey discussion
- current setup
- measurements
- customer requirements
- customer preferences and assumptions
- proposed scope of works
- timeline
- assumptions
- exclusions
- terms/next steps

You must:
- Use plain professional UK English.
- Make the wording feel specific to the survey notes.
- Say "to be confirmed" where information is missing.
- Use the supplied survey date only if present.
- Use the supplied contractor/surveyor name only if present.
- Use selected photo captions if useful.
- Keep it suitable for a PDF quote pack.

You must not:
- Produce prices.
- Invent measurements.
- Invent names.
- Invent survey dates.
- Mention AI, ChatGPT or language models.
- Include internal pricing logic.
PROMPT;
    }

    private function estimateJsonSchema(): array
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
            ],
        ];
    }

    private function wordingJsonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'customer_message' => ['type' => 'string'],
                'site_visit_summary' => ['type' => 'string'],
                'existing_setup' => ['type' => 'string'],
                'measurements_summary' => ['type' => 'string'],
                'customer_requirements' => ['type' => 'string'],
                'preferences_and_assumptions' => ['type' => 'string'],
                'proposed_scope' => ['type' => 'string'],
                'timeline' => ['type' => 'string'],
                'assumptions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'exclusions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'terms' => ['type' => 'string'],
                'photo_caption_suggestions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'quote_file_id' => ['type' => 'number'],
                            'caption' => ['type' => 'string'],
                        ],
                        'required' => ['quote_file_id', 'caption'],
                    ],
                ],
                'missing_information' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'field' => ['type' => 'string'],
                            'question' => ['type' => 'string'],
                        ],
                        'required' => ['field', 'question'],
                    ],
                ],
            ],
            'required' => [
                'customer_message',
                'site_visit_summary',
                'existing_setup',
                'measurements_summary',
                'customer_requirements',
                'preferences_and_assumptions',
                'proposed_scope',
                'timeline',
                'assumptions',
                'exclusions',
                'terms',
                'photo_caption_suggestions',
                'missing_information',
            ],
        ];
    }
}