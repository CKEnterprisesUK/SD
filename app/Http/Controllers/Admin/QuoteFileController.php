<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuoteFileController extends Controller
{
    public function store(Request $request, Quote $quote)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'type' => ['required', 'in:photo,plan,document,quote_pdf,other'],
            'room_or_area' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:2000'],
        ]);

        $uploadedFile = $validated['file'];

        $path = $uploadedFile->store('quote-files/' . $quote->id, 'public');

        $quote->files()->create([
            'uploaded_by_user_id' => auth()->id(),
            'type' => $validated['type'],
            'disk' => 'public',
            'path' => $path,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $uploadedFile->getMimeType(),
            'size_bytes' => $uploadedFile->getSize(),
            'room_or_area' => $validated['room_or_area'] ?? null,
            'caption' => $validated['caption'] ?? null,
        ]);

        return redirect()
            ->route('admin.quotes.survey', $quote)
            ->with('status', 'File uploaded successfully.');
    }

    public function destroy(Quote $quote, QuoteFile $file)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($file->quote_id === $quote->id, 404);

        Storage::disk($file->disk)->delete($file->path);

        $file->delete();

        return redirect()
            ->route('admin.quotes.survey', $quote)
            ->with('status', 'File deleted successfully.');
    }
}