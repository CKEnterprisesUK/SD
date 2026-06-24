<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteLineItem;
use Illuminate\Http\Request;

class QuoteLineItemController extends Controller
{
    public function store(Request $request, Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'unit' => ['required', 'string', 'max:255'],
            'unit_amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'is_optional' => ['nullable', 'boolean'],
        ]);

        $quantity = (float) $validated['quantity'];
        $unitAmountPence = (int) round(((float) $validated['unit_amount']) * 100);
        $totalPence = (int) round($quantity * $unitAmountPence);

        $quote->lineItems()->create([
            'source' => 'manual',
            'type' => $validated['type'],
            'description' => $validated['description'],
            'quantity' => $quantity,
            'unit' => $validated['unit'],
            'unit_amount_pence' => $unitAmountPence,
            'total_pence' => $totalPence,
            'is_optional' => (bool) ($validated['is_optional'] ?? false),
            'sort_order' => $quote->lineItems()->count() + 1,
        ]);

        $quote->recalculateTotals();

        return redirect()
            ->route('admin.quotes.show', $quote)
            ->with('status', 'Quote line item added.');
    }

    public function destroy(Quote $quote, QuoteLineItem $lineItem)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($lineItem->quote_id === $quote->id, 404);

        $lineItem->delete();

        $quote->recalculateTotals();

        return redirect()
            ->route('admin.quotes.show', $quote)
            ->with('status', 'Quote line item deleted.');
    }
}