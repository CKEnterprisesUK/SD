<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     *
     * Admins receive a paginated list of projects to render in the
     * projects table; other roles receive null.
     */
    public function index(): View
    {
        $user = auth()->user();

        $projects = $user->isAdmin()
            ? Project::with('customer')->latest()->paginate(15)
            : null;

        return view('dashboard', ['projects' => $projects]);
    }
}
