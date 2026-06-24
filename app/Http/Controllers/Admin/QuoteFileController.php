<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

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

        $directory = public_path('uploads/quote-files/' . $quote->id);

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $extension = $uploadedFile->getClientOriginalExtension() ?: 'file';

        $filename = now()->format('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;

        $uploadedFile->move($directory, $filename);

        $path = 'uploads/quote-files/' . $quote->id . '/' . $filename;

        $quote->files()->create([
            'uploaded_by_user_id' => auth()->id(),
            'type' => $validated['type'],
            'disk' => 'public_uploads',
            'path' => $path,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $uploadedFile->getClientMimeType(),
            'size_bytes' => File::size(public_path($path)),
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

        if ($file->path && File::exists(public_path($file->path))) {
            File::delete(public_path($file->path));
        }

        $file->delete();

        return redirect()
            ->route('admin.quotes.survey', $quote)
            ->with('status', 'File deleted successfully.');
    }
}