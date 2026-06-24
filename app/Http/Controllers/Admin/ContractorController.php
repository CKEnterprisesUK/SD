<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\ContractorActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ContractorController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $contractors = Contractor::with('user')
            ->latest()
            ->paginate(10);

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
            'email' => ['required', 'email', 'max:255', 'unique:contractors,email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'day_rate' => ['required', 'numeric', 'min:0'],
            'send_invite' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(40)),
            'role' => 'contractor',
            'status' => 'active',
        ]);

        $contractor = Contractor::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'day_rate_pence' => (int) round($validated['day_rate'] * 100),
            'status' => 'active',
        ]);

        $this->logContractorActivity(
            contractor: $contractor,
            action: 'created',
            description: 'Contractor account created.',
            oldValues: null,
            newValues: $contractor->only([
                'name',
                'email',
                'phone',
                'company_name',
                'day_rate_pence',
                'status',
            ])
        );

        if ($request->boolean('send_invite')) {
            Password::sendResetLink([
                'email' => $user->email,
            ]);

            $this->logContractorActivity(
                contractor: $contractor,
                action: 'invite_sent',
                description: 'Contractor invite/password setup email sent.',
            );
        }

        return redirect()
            ->route('admin.contractors.show', $contractor)
            ->with('status', 'Contractor created successfully.');
    }

    public function show(Contractor $contractor)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $contractor->load([
            'user',
            'activityLogs' => fn ($query) => $query->with('user')->latest()->limit(20),
        ]);

        return view('admin.contractors.show', [
            'contractor' => $contractor,
        ]);
    }

    public function edit(Contractor $contractor)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.contractors.edit', [
            'contractor' => $contractor,
        ]);
    }

    public function update(Request $request, Contractor $contractor)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'day_rate' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $oldValues = $contractor->only([
            'phone',
            'company_name',
            'day_rate_pence',
            'status',
        ]);

        $contractor->update([
            'phone' => $validated['phone'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'day_rate_pence' => (int) round($validated['day_rate'] * 100),
            'status' => $validated['status'],
        ]);

        if ($contractor->user) {
            $contractor->user->update([
                'status' => $validated['status'],
            ]);
        }

        $this->logContractorActivity(
            contractor: $contractor,
            action: 'updated',
            description: 'Contractor details updated.',
            oldValues: $oldValues,
            newValues: $contractor->fresh()->only([
                'phone',
                'company_name',
                'day_rate_pence',
                'status',
            ])
        );

        return redirect()
            ->route('admin.contractors.show', $contractor)
            ->with('status', 'Contractor updated successfully.');
    }

    public function sendInvite(Contractor $contractor)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if (! $contractor->user) {
            $user = User::create([
                'name' => $contractor->name,
                'email' => $contractor->email,
                'password' => Hash::make(Str::random(40)),
                'role' => 'contractor',
                'status' => 'active',
            ]);

            $contractor->update([
                'user_id' => $user->id,
            ]);

            $contractor->refresh();
        }

        Password::sendResetLink([
            'email' => $contractor->user->email,
        ]);

        $this->logContractorActivity(
            contractor: $contractor,
            action: 'invite_sent',
            description: 'Contractor invite/password setup email sent.',
        );

        return redirect()
            ->route('admin.contractors.show', $contractor)
            ->with('status', 'Invite/password reset email sent.');
    }

    private function logContractorActivity(
        Contractor $contractor,
        string $action,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        ContractorActivityLog::create([
            'contractor_id' => $contractor->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}