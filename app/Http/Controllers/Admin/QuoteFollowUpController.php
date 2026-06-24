<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteFollowUp;
use Illuminate\Http\Request;

class QuoteFollowUpController extends Controller
{
    public function store(Request $request, Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'due_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $quote->followUps()->create([
            'assigned_user_id' => $quote->assigned_user_id ?: auth()->id(),
            'due_at' => $validated['due_at'],
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()
            ->route('admin.quotes.show', $quote)
            ->with('status', 'Follow-up reminder added.');
    }

    public function complete(Quote $quote, QuoteFollowUp $followUp)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($followUp->quote_id === $quote->id, 404);

        $followUp->update([
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('admin.quotes.show', $quote)
            ->with('status', 'Follow-up marked as completed.');
    }
}