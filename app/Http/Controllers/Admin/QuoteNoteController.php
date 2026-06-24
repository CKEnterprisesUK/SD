<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteNote;
use Illuminate\Http\Request;

class QuoteNoteController extends Controller
{
    public function store(Request $request, Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'type' => ['required', 'in:general,measurement,requirement,consideration,risk,material,equipment,customer_comment,internal'],
            'room_or_area' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $quote->notes()->create([
            'created_by_user_id' => auth()->id(),
            'type' => $validated['type'],
            'room_or_area' => $validated['room_or_area'] ?? null,
            'body' => $validated['body'],
            'sort_order' => 0,
        ]);

        if ($quote->status === 'draft') {
            $quote->update([
                'status' => 'survey_in_progress',
            ]);
        }

        return redirect()
            ->route('admin.quotes.survey', $quote)
            ->with('status', 'Survey note added.');
    }

    public function destroy(Quote $quote, QuoteNote $note)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($note->quote_id === $quote->id, 404);

        $note->delete();

        return redirect()
            ->route('admin.quotes.survey', $quote)
            ->with('status', 'Survey note deleted.');
    }
}