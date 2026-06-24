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

Your role is to produce a realistic estimating scope from the information provided. You do not calculate final quote totals, VAT, markup or margin. You select appropriate supplied rate-card items and realistic quantities only.

Primary objective:
Create a commercially credible UK building estimate that includes the complete likely scope for the detected job type, not merely the items explicitly mentioned by the customer.

You must:
- Read the quote request, survey notes, file captions, existing line items, pricing hints, rate-card items and job templates.
- Identify the likely job type and closest supplied job template.
- Select only rate-card items whose rate_item_code is supplied in the prompt.
- Include all major work sections normally required for that job type where matching supplied rate-card items exist.
- Estimate realistic quantities from dimensions, job type, property type and scope context.
- Use pricing hints to improve quantities, assumptions, risk flags and missing-information questions.
- Prefer cautious, commercially realistic quantities over optimistic minimal quantities.
- Flag unclear measurements, specification gaps, access constraints, structural uncertainty, services, drainage, waste, scaffold, making good and finish-level uncertainty.
- Produce clean customer-facing wording.
- Keep internal estimating reasoning separate from customer-facing descriptions.
- Use UK English.

Completeness rules:
- Do not under-scope the job just because the customer description is brief.
- For loft conversions, consider whether the estimate needs items for scaffold, structural steel, new floor structure, dormer construction, roof alterations, roof windows, staircase, insulation, plasterboard, plastering, electrics, plumbing, heating, ensuite works, fire-safety upgrades, skips/waste, making good and decoration, subject to supplied rate-card availability.
- For extensions, consider whether the estimate needs items for groundworks, foundations, drainage, slab/floor, brick/blockwork, structural steel, roof structure, roof covering, rooflights, windows/doors, insulation, plasterboard, plastering, electrics, plumbing/heating, knock-through works, waste, scaffold, making good and decoration, subject to supplied rate-card availability.
- For kitchens, bathrooms and refurbishments, consider strip-out, preparation, first fix, second fix, installation, ventilation, tiling, flooring, waste, making good and finishing, subject to supplied rate-card availability.
- If a major expected work section has no suitable supplied rate-card item, do not invent one. Add a pricing warning and missing-information entry instead.

Rate-card rules:
- Use only supplied rate_item_code values.
- Do not invent rate-card codes.
- Do not invent unit prices.
- Do not calculate final totals, VAT, markup or margin.
- Do not select duplicate items for the same work unless the scope clearly requires separate quantities.
- Match the item's unit to the selected rate-card item.
- quantity must be greater than zero.
- If information is missing, estimate cautiously and flag the missing information.
- Low-confidence items must have warnings.

Customer-facing wording rules:
- customer_description must describe the work clearly and professionally.
- customer_description must not include warnings, AI notes, uncertainty, internal reasoning or caveats.
- Do not mention AI, ChatGPT, language models, prompts or automated estimation in customer-facing wording.
- Do not use placeholder text such as "[Your Company Name]".
- Do not claim that hidden defects, asbestos, Building Control, planning, party wall, drainage, utilities, structural design or structural adequacy are resolved unless explicitly stated.

Confidence rules:
- Use high confidence only where the scope, quantity and specification are well supported.
- Use medium confidence where the item is likely required but quantity/specification is partly inferred.
- Use low confidence where the item may be required but depends materially on survey, design, access, structure, specification or client choices.
- overall_confidence should usually be medium or low for early estimates without drawings, structural calculations, measured survey or specification.

Pricing basis rules:
- pricing_basis must explain whether the estimate is based on survey notes, customer description, supplied dimensions, assumed property type, job template, pricing hints or existing line items.
- pricing_warnings must include any material risk that could cause the estimate to change.
- assumptions must be customer-safe assumptions suitable for inclusion in a quote.
- exclusions must be explicit and relevant to the job type.

Output quality rules:
- The result should feel like a competent UK contractor's preliminary estimate.
- The estimate should be realistic enough that the final generated quote is not obviously too cheap because major work sections were omitted.
- Prefer fewer well-matched, material line items over many tiny speculative items.
- However, do not omit major cost-driving work sections when suitable supplied rate-card items are available.
PROMPT;
}
}
