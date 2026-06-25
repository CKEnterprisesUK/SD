<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;

class QuotePackController extends Controller
{
    public function show(Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $quote->load([
            'customer.contacts',
            'lineItems',
            'notes.creator',
            'files.uploadedBy',
        ]);

        return view('admin.quotes.pack', [
            'quote' => $quote,
            'photos' => $this->quotePhotos($quote),
        ]);
    }

    public function update(Request $request, Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'final_customer_message' => ['nullable', 'string'],
            'final_site_visit_summary' => ['nullable', 'string'],
            'final_existing_setup' => ['nullable', 'string'],
            'final_measurements_summary' => ['nullable', 'string'],
            'final_customer_requirements' => ['nullable', 'string'],
            'final_preferences_assumptions' => ['nullable', 'string'],
            'final_scope' => ['nullable', 'string'],
            'final_timeline' => ['nullable', 'string'],
            'final_assumptions' => ['nullable', 'string'],
            'final_exclusions' => ['nullable', 'string'],
            'final_terms' => ['nullable', 'string'],
        ]);

        $quote->forceFill($validated)->save();

        return redirect()
            ->route('admin.quotes.pack', $quote)
            ->with('status', 'Customer pack saved.');
    }

    public function updatePhotos(Request $request, Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'photos' => ['nullable', 'array'],
            'photos.*.include' => ['nullable', 'boolean'],
            'photos.*.caption' => ['nullable', 'string', 'max:255'],
            'photos.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $quote->load('files');

        $photoData = $validated['photos'] ?? [];

        foreach ($this->quotePhotos($quote) as $file) {
            $data = $photoData[$file->id] ?? [];

            $file->forceFill([
                'include_in_quote_pack' => ! empty($data['include']),
                'quote_pack_caption' => $data['caption'] ?? null,
                'quote_pack_sort_order' => (int) ($data['sort_order'] ?? 0),
            ])->save();
        }

        return redirect()
            ->route('admin.quotes.pack', $quote)
            ->with('status', 'Quote-pack photos updated.');
    }

    private function quotePhotos(Quote $quote)
    {
        return $quote->files
            ->filter(function ($file) {
                return str_starts_with((string) $file->mime_type, 'image/');
            })
            ->sortBy([
                fn ($a, $b) => ((int) ($a->quote_pack_sort_order ?? 0)) <=> ((int) ($b->quote_pack_sort_order ?? 0)),
                fn ($a, $b) => $a->id <=> $b->id,
            ])
            ->values();
    }
}