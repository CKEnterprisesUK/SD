<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class QuotePackTemplateController extends Controller
{
    public function edit()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.settings.quote-pack', [
            'settings' => PortalSetting::current(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'front_page' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'back_page' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'remove_front_page' => ['nullable', 'boolean'],
            'remove_back_page' => ['nullable', 'boolean'],
        ]);

        $settings = PortalSetting::current();

        $frontPath = $settings->quote_pack_front_page_path;
        $backPath = $settings->quote_pack_back_page_path;

        if ($request->boolean('remove_front_page')) {
            $this->deletePublicFile($frontPath);
            $frontPath = null;
        }

        if ($request->boolean('remove_back_page')) {
            $this->deletePublicFile($backPath);
            $backPath = null;
        }

        if ($request->hasFile('front_page')) {
            $this->deletePublicFile($frontPath);
            $frontPath = $this->storeTemplateImage($request, 'front_page');
        }

        if ($request->hasFile('back_page')) {
            $this->deletePublicFile($backPath);
            $backPath = $this->storeTemplateImage($request, 'back_page');
        }

        $settings->forceFill([
            'quote_pack_front_page_path' => $frontPath,
            'quote_pack_back_page_path' => $backPath,
        ])->save();

        return redirect()
            ->route('admin.settings.quote-pack.edit')
            ->with('status', 'Quote pack pages updated.');
    }

    private function storeTemplateImage(Request $request, string $field): string
    {
        $file = $request->file($field);

        $directory = public_path('uploads/quote-pack-templates');

        File::ensureDirectoryExists($directory);

        $filename = $field . '-' . now()->format('YmdHis') . '-' . Str::random(8) . '.' . strtolower($file->getClientOriginalExtension());

        $file->move($directory, $filename);

        return 'uploads/quote-pack-templates/' . $filename;
    }

    private function deletePublicFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        $fullPath = public_path($path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }
}