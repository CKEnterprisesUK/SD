<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     *
     * The dashboard is a role-aware grid of tool tiles (Contractors, Customers,
     * Quotes, Projects, etc.). The full projects listing lives on its own page
     * at admin.projects.index, reached via the Projects tile.
     */
    public function index(): View
    {
        return view('dashboard');
    }
}
