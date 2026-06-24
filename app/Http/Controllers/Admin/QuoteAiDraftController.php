<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRateCard;
use App\Models\PricingRateItem;
use App\Models\Quote;
use App\Models\QuoteAiDraft;
use App\Models\QuoteAiDraftItem;
use App\Services\QuotePricingCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteAiDraftController extends Controller
{
    public function accept(Quote $quote, QuoteAiDraftItem $item)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->abortIfItemDoesNotBelongToQuote($quote, $item);

        $item->update(['status' => 'accepted']);

        return redirect()->route('admin.quotes.pricing', $quote)->with('status', 'AI draft item accepted.');
    }

    public function reject(Quote $quote, QuoteAiDraftItem $item)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->abortIfItemDoesNotBelongToQuote($quote, $item);

        $item->update(['status' => 'rejected']);

        return redirect()->route('admin.quotes.pricing', $quote)->with('status', 'AI draft item rejected.');
    }

    public function update(Request $request, Quote $quote, QuoteAiDraftItem $item, QuotePricingCalculator $calculator)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->abortIfItemDoesNotBelongToQuote($quote, $item);

        $validated = $request->validate([
            'pricing_rate_item_id' => ['required', 'exists:pricing_rate_items,id'],
            'clean_customer_description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'confidence' => ['required', 'in:low,medium,high'],
            'warnings' => ['nullable', 'string'],
        ]);

        $rateItem = PricingRateItem::findOrFail($validated['pricing_rate_item_id']);
        $rateCard = PricingRateCard::activeOrCreateDefault();
        $calculated = $calculator->calculate($rateItem, (float) $validated['quantity'], $rateCard, $validated['confidence']);

        $item->update(array_merge($calculated, [
            'pricing_rate_item_id' => $rateItem->id,
            'category' => $rateItem->category,
            'rate_item_code' => $rateItem->code,
            'clean_customer_description' => $validated['clean_customer_description'],
            'quantity' => $validated['quantity'],
            'unit' => $rateItem->unit,
            'confidence' => $validated['confidence'],
            'pricing_source' => 'rate_card',
            'warnings' => $this->linesToArray($validated['warnings'] ?? null),
            'status' => 'accepted',
        ]));

        return redirect()->route('admin.quotes.pricing', $quote)->with('status', 'AI draft item updated and accepted.');
    }

    public function applyAccepted(Quote $quote, QuoteAiDraft $draft)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($draft->quote_id === $quote->id, 404);

        $acceptedItems = $draft->items()->where('status', 'accepted')->get();

        if ($acceptedItems->isEmpty()) {
            return redirect()->route('admin.quotes.pricing', $quote)->withErrors([
                'ai_draft' => 'Accept at least one AI draft item before applying it to the quote.',
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

        return redirect()->route('admin.quotes.pricing', $quote)->with('status', 'Accepted AI draft items applied to the quote.');
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

        return redirect()->route('admin.quotes.pack', $quote)->with('status', 'AI customer wording applied to the customer pack.');
    }

    private function abortIfItemDoesNotBelongToQuote(Quote $quote, QuoteAiDraftItem $item): void
    {
        abort_unless($item->quote_id === $quote->id, 404);
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
