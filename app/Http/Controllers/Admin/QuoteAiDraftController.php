<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRateCard;
use App\Models\Quote;
use App\Models\QuoteAiDraft;
use App\Models\QuoteAiDraftItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteAiDraftController extends Controller
{
    public function accept(Request $request, Quote $quote, QuoteAiDraftItem $item)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->abortIfItemDoesNotBelongToQuote($quote, $item);

        $item->update(['status' => 'accepted']);
        $item->refresh();

        if ($this->wantsJson($request)) {
            return $this->itemJson($item, 'Item accepted.');
        }

        return redirect()->route('admin.quotes.pricing', $quote)->with('status', 'Item accepted.');
    }

    public function reject(Request $request, Quote $quote, QuoteAiDraftItem $item)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->abortIfItemDoesNotBelongToQuote($quote, $item);

        $item->update(['status' => 'rejected']);
        $item->refresh();

        if ($this->wantsJson($request)) {
            return $this->itemJson($item, 'Item rejected.');
        }

        return redirect()->route('admin.quotes.pricing', $quote)->with('status', 'Item rejected.');
    }

    public function update(Request $request, Quote $quote, QuoteAiDraftItem $item)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->abortIfItemDoesNotBelongToQuote($quote, $item);

        $validated = $request->validate([
            'clean_customer_description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'unit' => ['required', 'string', 'max:50'],
            'low_estimate_ex_vat' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'likely_estimate_ex_vat' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'high_estimate_ex_vat' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'confidence' => ['required', 'in:low,medium,high'],
            'pricing_basis' => ['required', 'string', 'max:80'],
            'estimate_explanation' => ['nullable', 'string'],
            'warnings' => ['nullable', 'string'],
        ]);

        $rateCard = PricingRateCard::activeOrCreateDefault();
        $vatPercent = (float) $rateCard->vat_percent;

        $quantity = max((float) $validated['quantity'], 0.01);

        $lowPence = $this->poundsToPence($validated['low_estimate_ex_vat']);
        $likelyPence = $this->poundsToPence($validated['likely_estimate_ex_vat']);
        $highPence = $this->poundsToPence($validated['high_estimate_ex_vat']);

        $unitAmountPence = (int) round($likelyPence / $quantity);
        $vatPence = $this->percentOf($likelyPence, $vatPercent);

        $item->update([
            'pricing_rate_item_id' => null,
            'rate_item_code' => null,
            'clean_customer_description' => $validated['clean_customer_description'],
            'quantity' => $quantity,
            'unit' => $validated['unit'],
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
            'confidence' => $validated['confidence'],
            'pricing_source' => 'ai_estimate',
            'pricing_basis' => $validated['pricing_basis'],
            'estimate_explanation' => $validated['estimate_explanation'] ?? null,
            'warnings' => $this->linesToArray($validated['warnings'] ?? null),
            'status' => 'accepted',
        ]);

        $item->refresh();

        if ($this->wantsJson($request)) {
            return $this->itemJson($item, 'Item saved and accepted.');
        }

        return redirect()->route('admin.quotes.pricing', $quote)->with('status', 'Item saved and accepted.');
    }

    public function applyAccepted(Quote $quote, QuoteAiDraft $draft)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($draft->quote_id === $quote->id, 404);

        $acceptedItems = $draft->items()->where('status', 'accepted')->get();

        if ($acceptedItems->isEmpty()) {
            return redirect()->route('admin.quotes.pricing', $quote)->withErrors([
                'ai_draft' => 'Accept at least one item before applying it to the quote.',
            ]);
        }

        DB::transaction(function () use ($quote, $draft, $acceptedItems) {
            $nextSortOrder = (int) $quote->lineItems()->max('sort_order');

            foreach ($acceptedItems as $item) {
                $nextSortOrder++;

                $unitAmountPence = $item->quantity > 0
                    ? (int) round($item->subtotal_pence / (float) $item->quantity)
                    : $item->subtotal_pence;

                $quote->lineItems()->create([
                    'source' => 'ai_reviewed',
                    'type' => $item->category,
                    'description' => $item->clean_customer_description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_amount_pence' => max($unitAmountPence, 0),
                    'total_pence' => max((int) $item->subtotal_pence, 0),
                    'is_optional' => false,
                    'sort_order' => $nextSortOrder,
                ]);

                $item->update(['status' => 'applied']);
            }

            $draft->update(['status' => 'applied']);
            $quote->recalculateTotals();
        });

        return redirect()
            ->route('admin.quotes.pricing', $quote)
            ->with('status', 'Accepted items applied to the quote.');
    }

    public function applyWording(Quote $quote, QuoteAiDraft $draft)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($draft->quote_id === $quote->id, 404);

        $quote->update([
            'final_customer_message' => $draft->customer_message,
            'final_scope' => $draft->scope_of_works,
            'final_assumptions' => is_array($draft->assumptions) ? implode("\n", $draft->assumptions) : $draft->assumptions,
            'final_exclusions' => is_array($draft->exclusions) ? implode("\n", $draft->exclusions) : $draft->exclusions,
            'final_timeline' => $draft->timeline,
            'final_terms' => $draft->terms,
            'status' => 'ai_compiled',
        ]);

        return redirect()
            ->route('admin.quotes.pack', $quote)
            ->with('status', 'Customer wording applied to the quote pack.');
    }

    private function abortIfItemDoesNotBelongToQuote(Quote $quote, QuoteAiDraftItem $item): void
    {
        abort_unless($item->quote_id === $quote->id, 404);
    }

    private function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    private function itemJson(QuoteAiDraftItem $item, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'item' => [
                'id' => $item->id,
                'status' => $item->status,
                'status_label' => ucfirst($item->status),
                'quantity' => (string) $item->quantity,
                'unit' => $item->unit,
                'subtotal' => $item->subtotal,
                'vat' => $item->vat,
                'total' => $item->total,
                'low_total' => $item->low_total,
                'likely_total' => $item->likely_total,
                'high_total' => $item->high_total,
                'confidence' => $item->confidence,
                'pricing_basis' => $item->pricing_basis,
                'warnings' => $item->warnings ?? [],
            ],
        ]);
    }

    private function poundsToPence(mixed $value): int
    {
        return max((int) round((float) $value * 100), 0);
    }

    private function percentOf(int $amountPence, float $percent): int
    {
        return (int) round($amountPence * ($percent / 100));
    }

    private function linesToArray(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}