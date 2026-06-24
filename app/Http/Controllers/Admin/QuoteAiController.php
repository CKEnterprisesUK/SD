<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteAiGeneration;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class QuoteAiController extends Controller
{
    public function compile(Request $request, Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model', 'gpt-4o-mini');

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
        ]);

        $inputPayload = $this->buildInputPayload($quote);

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
                ->timeout(90)
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
                        'type' => 'json_object',
                    ],
                    'temperature' => 0.3,
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

            $this->applyAiOutputToQuote($quote, $decoded);

            $generation->update([
                'status' => 'completed',
                'output_payload' => json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'error_message' => null,
            ]);

            return redirect()
                ->route('admin.quotes.pack', $quote)
                ->with('status', 'AI customer pack generated successfully. Please review before sending.');
        } catch (RequestException $exception) {
            $message = $exception->response?->json('error.message')
                ?: $exception->getMessage();

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
                    'ai' => 'AI compile failed: ' . $exception->getMessage(),
                ]);
        }
    }

    private function buildInputPayload(Quote $quote): array
    {
        return [
            'instruction' => 'Generate a customer-facing quote pack and suggested line items from the supplied quote data. Return JSON only.',
            'quote' => [
                'quote_number' => $quote->quote_number,
                'title' => $quote->title,
                'status' => $quote->status,
                'site_address' => $quote->site_address,
                'summary' => $quote->summary,
                'internal_notes' => $quote->internal_notes,
                'current_total' => $quote->total,
            ],
            'customer' => [
                'name' => $quote->customer?->display_name,
                'address' => $quote->customer?->address,
                'contacts' => $quote->customer?->contacts?->map(fn ($contact) => [
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'role' => $contact->role,
                    'is_primary' => $contact->is_primary,
                ])->values()->all() ?? [],
            ],
            'survey_notes' => $quote->notes->map(fn ($note) => [
                'type' => $note->type,
                'room_or_area' => $note->room_or_area,
                'body' => $note->body,
                'created_by' => $note->creator?->name,
                'created_at' => $note->created_at?->toDateTimeString(),
            ])->values()->all(),
            'photos_and_files' => $quote->files->map(fn ($file) => [
                'type' => $file->type,
                'room_or_area' => $file->room_or_area,
                'caption' => $file->caption,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'uploaded_by' => $file->uploadedBy?->name,
            ])->values()->all(),
            'existing_line_items' => $quote->lineItems->map(fn ($lineItem) => [
                'source' => $lineItem->source,
                'type' => $lineItem->type,
                'description' => $lineItem->description,
                'quantity' => $lineItem->quantity,
                'unit' => $lineItem->unit,
                'unit_amount' => $lineItem->unit_amount,
                'total' => $lineItem->total,
                'is_optional' => $lineItem->is_optional,
            ])->values()->all(),
            'required_json_schema' => [
                'customer_message' => 'string',
                'scope_of_works' => 'string',
                'estimated_timeline' => 'string',
                'assumptions' => 'string',
                'exclusions' => 'string',
                'terms' => 'string',
                'suggested_line_items' => [
                    [
                        'type' => 'string',
                        'description' => 'string',
                        'quantity' => 'number',
                        'unit' => 'string',
                        'unit_amount' => 'number',
                        'reasoning' => 'string',
                    ],
                ],
                'missing_information' => ['string'],
                'pricing_warnings' => ['string'],
            ],
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are assisting a UK building firm to draft a professional customer quote.

You must return valid JSON only. Do not wrap it in markdown.

The quote must be professional, clear, practical and customer-friendly.

Important rules:
- Do not claim that anything is guaranteed unless explicitly stated in the input.
- Do not invent planning permission, building control, structural engineer, utility, asbestos, drainage or party wall conclusions.
- If information is missing, add it to "missing_information".
- If pricing is uncertain, add it to "pricing_warnings".
- Suggested prices are draft estimates only. They must be reviewed by the team.
- Keep wording suitable for a homeowner/customer.
- Avoid excessive sales language.
- Use UK English.
- Do not mention AI or ChatGPT.

Return JSON with exactly these top-level keys:
{
  "customer_message": "...",
  "scope_of_works": "...",
  "estimated_timeline": "...",
  "assumptions": "...",
  "exclusions": "...",
  "terms": "...",
  "suggested_line_items": [
    {
      "type": "works",
      "description": "...",
      "quantity": 1,
      "unit": "item",
      "unit_amount": 0,
      "reasoning": "..."
    }
  ],
  "missing_information": [],
  "pricing_warnings": []
}

For suggested_line_items:
- Use numeric unit_amount in pounds, not pence.
- Only include line items that are supported by the survey notes or quote summary.
- If existing line items already cover the scope, you may return an empty suggested_line_items array.
PROMPT;
    }

    private function applyAiOutputToQuote(Quote $quote, array $decoded): void
    {
        DB::transaction(function () use ($quote, $decoded) {
            $quote->update([
                'status' => 'ai_compiled',
                'final_customer_message' => $decoded['customer_message'] ?? null,
                'final_scope' => $decoded['scope_of_works'] ?? null,
                'final_timeline' => $decoded['estimated_timeline'] ?? null,
                'final_assumptions' => $this->normaliseTextList($decoded['assumptions'] ?? null),
                'final_exclusions' => $this->normaliseTextList($decoded['exclusions'] ?? null),
                'final_terms' => $this->buildTermsText($decoded),
            ]);

            $quote->lineItems()
                ->where('source', 'ai_suggested')
                ->delete();

            $suggestedLineItems = $decoded['suggested_line_items'] ?? [];

            if (is_array($suggestedLineItems)) {
                foreach ($suggestedLineItems as $index => $item) {
                    if (empty($item['description'])) {
                        continue;
                    }

                    $quantity = max(0.01, (float) ($item['quantity'] ?? 1));
                    $unitAmountPounds = max(0, (float) ($item['unit_amount'] ?? 0));
                    $unitAmountPence = (int) round($unitAmountPounds * 100);
                    $totalPence = (int) round($quantity * $unitAmountPence);

                    $description = $item['description'];

                    if (! empty($item['reasoning'])) {
                        $description .= ' — ' . $item['reasoning'];
                    }

                    $quote->lineItems()->create([
                        'source' => 'ai_suggested',
                        'type' => $item['type'] ?? 'works',
                        'description' => mb_substr($description, 0, 255),
                        'quantity' => $quantity,
                        'unit' => $item['unit'] ?? 'item',
                        'unit_amount_pence' => $unitAmountPence,
                        'total_pence' => $totalPence,
                        'is_optional' => false,
                        'sort_order' => $quote->lineItems()->count() + $index + 1,
                    ]);
                }
            }

            $quote->recalculateTotals();
        });
    }

    private function normaliseTextList(mixed $value): ?string
    {
        if (is_array($value)) {
            return collect($value)
                ->filter()
                ->map(fn ($item) => '- ' . $item)
                ->implode("\n");
        }

        return $value ?: null;
    }

    private function buildTermsText(array $decoded): ?string
    {
        $parts = [];

        if (! empty($decoded['terms'])) {
            $parts[] = $this->normaliseTextList($decoded['terms']);
        }

        if (! empty($decoded['missing_information'])) {
            $parts[] = "Information still to confirm:\n" . $this->normaliseTextList($decoded['missing_information']);
        }

        if (! empty($decoded['pricing_warnings'])) {
            $parts[] = "Pricing notes:\n" . $this->normaliseTextList($decoded['pricing_warnings']);
        }

        return count($parts) ? implode("\n\n", array_filter($parts)) : null;
    }
}