<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortalSettingsController extends Controller
{
    public function edit()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.settings.edit', [
            'settings' => PortalSetting::current(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $settings = PortalSetting::current();

        $validated = $request->validate([
            'portal_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string'],
            'company_number' => ['nullable', 'string', 'max:100'],
            'vat_number' => ['nullable', 'string', 'max:100'],
            'primary_colour' => ['required', 'string', 'max:20'],
            'accounts_email' => ['nullable', 'email', 'max:255'],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:365'],
            'invoice_wording' => ['nullable', 'string'],
            'pdf_footer' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        unset($validated['logo']);

        $settings->update($validated);

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'Portal settings updated.');
    }
}