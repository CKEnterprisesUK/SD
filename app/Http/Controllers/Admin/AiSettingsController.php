<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalSetting;
use Illuminate\Http\Request;

class AiSettingsController extends Controller
{
    public function edit()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.settings.ai', [
            'settings' => PortalSetting::current(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'ai_enabled' => ['nullable', 'boolean'],
            'ai_estimate_model' => ['nullable', 'string', 'max:120'],
            'ai_wording_model' => ['nullable', 'string', 'max:120'],
            'ai_default_tone' => ['nullable', 'string', 'max:80'],
            'ai_temperature' => ['required', 'numeric', 'min:0', 'max:2'],
        ]);

        $settings = PortalSetting::current();

        $settings->forceFill([
            'ai_enabled' => $request->boolean('ai_enabled'),
            'ai_estimate_model' => $validated['ai_estimate_model'] ?: null,
            'ai_wording_model' => $validated['ai_wording_model'] ?: null,
            'ai_default_tone' => $validated['ai_default_tone'] ?: null,
            'ai_temperature' => $validated['ai_temperature'],
        ])->save();

        return redirect()
            ->route('admin.settings.ai.edit')
            ->with('status', 'AI settings updated.');
    }
}