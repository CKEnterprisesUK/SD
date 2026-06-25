<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteLineItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteLineItemController extends Controller
{
    public function store(Request $request, Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:1000'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'unit' => ['required', 'string', 'max:50'],
            'unit_amount' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'is_optional' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($quote, $validated, $request) {
            $quantity = (float) $validated['quantity'];
            $unitAmountPence = $this->poundsToPence($validated['unit_amount']);
            $totalPence = (int) round($quantity * $unitAmountPence);

            $nextSortOrder = ((int) $quote->lineItems()->max('sort_order')) + 1;

            $quote->lineItems()->create([
                'source' => 'manual',
                'type' => $validated['type'],
                'description' => $validated['description'],
                'quantity' => $quantity,
                'unit' => $validated['unit'],
                'unit_amount_pence' => $unitAmountPence,
                'total_pence' => $totalPence,
                'is_optional' => $request->boolean('is_optional'),
                'sort_order' => $nextSortOrder,
            ]);

            $quote->recalculateTotals();
        });

        return redirect()
            ->route('admin.quotes.pricing', $quote)
            ->with('status', 'Line item added.');
    }

    public function update(Request $request, Quote $quote, QuoteLineItem $lineItem)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless((int) $lineItem->quote_id === (int) $quote->id, 404);

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:1000'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'unit' => ['required', 'string', 'max:50'],
            'unit_amount' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'is_optional' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($quote, $lineItem, $validated, $request) {
            $quantity = (float) $validated['quantity'];
            $unitAmountPence = $this->poundsToPence($validated['unit_amount']);
            $totalPence = (int) round($quantity * $unitAmountPence);

            $lineItem->update([
                'type' => $validated['type'],
                'description' => $validated['description'],
                'quantity' => $quantity,
                'unit' => $validated['unit'],
                'unit_amount_pence' => $unitAmountPence,
                'total_pence' => $totalPence,
                'is_optional' => $request->boolean('is_optional'),
            ]);

            $quote->recalculateTotals();
        });

        return redirect()
            ->route('admin.quotes.pricing', $quote)
            ->with('status', 'Line item updated.');
    }

    public function destroy(Quote $quote, QuoteLineItem $lineItem)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless((int) $lineItem->quote_id === (int) $quote->id, 404);

        DB::transaction(function () use ($quote, $lineItem) {
            $lineItem->delete();
            $quote->recalculateTotals();
        });

        return redirect()
            ->route('admin.quotes.pricing', $quote)
            ->with('status', 'Line item deleted.');
    }

    private function poundsToPence(mixed $value): int
    {
        return max((int) round((float) $value * 100), 0);
    }
}