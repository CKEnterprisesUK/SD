<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerActivityLogger;
use App\Services\CustomerPortalInviteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $customers = Customer::query()
            ->with('primaryContact')
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhereHas('contacts', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('role', 'like', "%{$search}%");
                        });
                });
            })
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('company_name')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'filters' => $validated,
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.customers.create');
    }

    public function store(Request $request, CustomerPortalInviteService $inviter)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,prospect,archived'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'primary_contact' => ['nullable', 'array'],
            'primary_contact.email' => ['nullable', 'email', 'max:255'],
            'primary_contact.phone' => ['nullable', 'string', 'max:255'],
            'primary_contact.role' => ['nullable', 'string', 'max:255'],

            'contacts' => ['nullable', 'array'],
            'contacts.*.name' => ['nullable', 'string', 'max:255'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:255'],
            'contacts.*.role' => ['nullable', 'string', 'max:255'],
            'contacts.*.invite' => ['nullable', 'boolean'],
            'invite_to_portal' => ['nullable', 'boolean'],
        ]);

        $customer = DB::transaction(function () use ($validated) {
            $customer = Customer::create([
                'created_by_user_id' => auth()->id(),
                'name' => $validated['name'],
                'company_name' => $validated['company_name'] ?? null,
                'status' => $validated['status'],
                'address' => $validated['address'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncContacts($customer, $validated);

            return $customer;
        });

        CustomerActivityLogger::created($customer);

        $customer->load('contacts');

        $status = 'Customer created successfully.';
        $invitedCount = 0;

        // Invite the main contact when requested.
        if (! empty($validated['invite_to_portal'])) {
            $primary = $customer->primaryContact ?? $customer->contacts->firstWhere('email', '!=', null);

            if ($primary && $primary->email) {
                $inviter->invite($customer, $primary->email, $primary->name ?: $customer->name, $primary->id);
                $invitedCount++;
            } else {
                $status = 'Customer created. No main contact email was available to send a portal invite.';
            }
        }

        // Invite any additional contacts that were flagged for an invite.
        $invitedCount += $this->inviteFlaggedContacts($customer, $validated, $inviter);

        if ($invitedCount > 0) {
            $status = $invitedCount === 1
                ? 'Customer created and Green Street Portal invite sent.'
                : "Customer created and {$invitedCount} Green Street Portal invites sent.";
        }

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', $status);
    }

   public function show(Customer $customer)
{
    abort_unless(auth()->user()->isAdmin(), 403);

    $customer->load([
        'contacts',
        'primaryContact',
        'activityLogs.user',
    ]);

    $currentQuotes = $customer->quotes()
        ->whereNotIn('status', ['accepted', 'declined', 'cancelled'])
        ->latest()
        ->get();

    $recentQuotes = $customer->quotes()
        ->whereIn('status', ['accepted', 'declined', 'cancelled'])
        ->latest()
        ->limit(5)
        ->get();

    $projects = $customer->projects()->latest()->get();

    return view('admin.customers.show', [
        'customer' => $customer,
        'currentQuotes' => $currentQuotes,
        'recentQuotes' => $recentQuotes,
        'projects' => $projects,
    ]);
}

    public function edit(Customer $customer)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $customer->load('contacts');

        return view('admin.customers.edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer, CustomerPortalInviteService $inviter)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,prospect,archived'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'primary_contact' => ['nullable', 'array'],
            'primary_contact.email' => ['nullable', 'email', 'max:255'],
            'primary_contact.phone' => ['nullable', 'string', 'max:255'],
            'primary_contact.role' => ['nullable', 'string', 'max:255'],

            'contacts' => ['nullable', 'array'],
            'contacts.*.name' => ['nullable', 'string', 'max:255'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:255'],
            'contacts.*.role' => ['nullable', 'string', 'max:255'],
            'contacts.*.invite' => ['nullable', 'boolean'],
        ]);

        $tracked = ['name', 'company_name', 'status', 'address', 'notes'];

        DB::transaction(function () use ($customer, $validated, $tracked, &$changes) {
            $original = $customer->only($tracked);

            $customer->update([
                'name' => $validated['name'],
                'company_name' => $validated['company_name'] ?? null,
                'status' => $validated['status'],
                'address' => $validated['address'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Capture field-level changes for the audit feed.
            $changes = [];

            foreach ($tracked as $field) {
                if (($original[$field] ?? null) !== $customer->{$field}) {
                    $changes[$field] = [$original[$field] ?? null, $customer->{$field}];
                }
            }

            $customer->contacts()->delete();

            $this->syncContacts($customer, $validated);
        });

        CustomerActivityLogger::updated($customer, $changes ?? []);

        // Send invites to any additional contacts newly flagged for one.
        $customer->load('contacts');
        $invitedCount = $this->inviteFlaggedContacts($customer, $validated, $inviter);

        $status = $invitedCount > 0
            ? ($invitedCount === 1
                ? 'Customer updated and Green Street Portal invite sent.'
                : "Customer updated and {$invitedCount} Green Street Portal invites sent.")
            : 'Customer updated successfully.';

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', $status);
    }

    /**
     * Persist the main contact (from the customer name + primary_contact fields)
     * as the primary contact, then any additional contacts that have an email.
     */
    private function syncContacts(Customer $customer, array $validated): void
    {
        $primary = $validated['primary_contact'] ?? [];

        // The main contact is always created from the customer name so it never
        // has to be typed twice.
        $customer->contacts()->create([
            'name' => $customer->name,
            'email' => $primary['email'] ?? null,
            'phone' => $primary['phone'] ?? null,
            'role' => $primary['role'] ?? 'Primary contact',
            'is_primary' => true,
            'receives_quotes' => true,
            'receives_invoices' => false,
            'portal_access_enabled' => false,
        ]);

        foreach ($validated['contacts'] ?? [] as $contact) {
            // Skip empty rows; an additional contact needs at least a name or email.
            if (empty($contact['name']) && empty($contact['email'])) {
                continue;
            }

            $customer->contacts()->create([
                'name' => $contact['name'] ?? null,
                'email' => $contact['email'] ?? null,
                'phone' => $contact['phone'] ?? null,
                'role' => $contact['role'] ?? null,
                'is_primary' => false,
                'receives_quotes' => true,
                'receives_invoices' => false,
                'portal_access_enabled' => false,
            ]);
        }
    }

    /**
     * Invite any additional contacts (matched by email) that were flagged for a
     * portal invite in the submitted form. Returns the number of invites sent.
     */
    private function inviteFlaggedContacts(Customer $customer, array $validated, CustomerPortalInviteService $inviter): int
    {
        $count = 0;

        foreach ($validated['contacts'] ?? [] as $contact) {
            if (empty($contact['invite']) || empty($contact['email'])) {
                continue;
            }

            $stored = $customer->contacts->firstWhere('email', $contact['email']);

            if (! $stored) {
                continue;
            }

            $inviter->invite($customer, $stored->email, $stored->name ?: $customer->name, $stored->id);
            $count++;
        }

        return $count;
    }
}