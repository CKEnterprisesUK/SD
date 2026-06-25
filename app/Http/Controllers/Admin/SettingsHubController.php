<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalSetting;

class SettingsHubController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.settings.index', [
            'settings' => PortalSetting::current(),
        ]);
    }
}