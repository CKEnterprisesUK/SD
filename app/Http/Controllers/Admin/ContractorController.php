<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use Illuminate\Http\Request;

class ContractorController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $contractors = Contractor::latest()->paginate(10);

        return view('admin.contractors.index', [
            'contractors' => $contractors,
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.contractors.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:contractors,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'day_rate' => ['required', 'numeric', 'min:0'],
        ]);

        Contractor::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'day_rate_pence' => (int) round($validated['day_rate'] * 100),
            'status' => 'active',
        ]);

        return redirect()
            ->route('admin.contractors.index')
            ->with('status', 'Contractor created successfully.');
    }
}