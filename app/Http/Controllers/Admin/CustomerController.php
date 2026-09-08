<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,prospect,archived'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'contacts' => ['nullable', 'array'],
            'contacts.*.name' => ['nullable', 'string', 'max:255'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:255'],
            'contacts.*.role' => ['nullable', 'string', 'max:255'],
            'primary_contact_index' => ['nullable', 'integer'],
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

        $status = 'Customer created successfully.';

        if (! empty($validated['invite_to_portal'])) {
            $contact = $customer->fresh('contacts')->primaryContact
                ?? $customer->contacts()->whereNotNull('email')->first();

            if ($contact && $contact->email) {
                $this->sendPortalInvite($customer, $contact->email, $contact->name ?: $customer->name, $contact->id);
                $status = 'Customer created and Green Street Portal invite sent.';
            } else {
                $status = 'Customer created. No contact email was available to send a portal invite.';
            }
        }

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', $status);
    }

    /**
     * Provision (or reuse) a customer portal User and send the Green Street
     * Portal password-setup email. Mirrors CustomerInviteController@send so the
     * create-time invite and the dashboard button behave identically.
     */
    private function sendPortalInvite(Customer $customer, string $email, string $name, ?int $customerContactId = null): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            // `role` and `status` are not mass-assignable on User, so set them
            // explicitly to guarantee an active customer-role account.
            $user = new User([
                'name' => $name,
                'email' => $email,
                'customer_id' => $customer->id,
            ]);
            $user->password = Hash::make(Str::random(40));
            $user->role = 'customer';
            $user->status = 'active';
            $user->save();
        }

        CustomerInvitation::create([
            'customer_id' => $customer->id,
            'customer_contact_id' => $customerContactId,
            'email' => $user->email,
            'invited_by_user_id' => auth()->id(),
            'user_id' => $user->id,
        ]);

        if ($customerContactId) {
            $customer->contacts()->whereKey($customerContactId)->update([
                'portal_access_enabled' => true,
            ]);
        }

        Password::sendResetLink(['email' => $user->email]);
    }

   public function show(Customer $customer)
{
    abort_unless(auth()->user()->isAdmin(), 403);

    $customer->load([
        'contacts',
        'primaryContact',
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

    public function update(Request $request, Customer $customer)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,prospect,archived'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'contacts' => ['nullable', 'array'],
            'contacts.*.name' => ['nullable', 'string', 'max:255'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:255'],
            'contacts.*.role' => ['nullable', 'string', 'max:255'],
            'primary_contact_index' => ['nullable', 'integer'],
        ]);

        DB::transaction(function () use ($customer, $validated) {
            $customer->update([
                'name' => $validated['name'],
                'company_name' => $validated['company_name'] ?? null,
                'status' => $validated['status'],
                'address' => $validated['address'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $customer->contacts()->delete();

            $this->syncContacts($customer, $validated);
        });

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', 'Customer updated successfully.');
    }

    private function syncContacts(Customer $customer, array $validated): void
    {
        $contacts = $validated['contacts'] ?? [];

        if (! count($contacts)) {
            return;
        }

        $primaryContactIndex = isset($validated['primary_contact_index'])
            ? (int) $validated['primary_contact_index']
            : 0;

        $createdAnyPrimary = false;

        foreach ($contacts as $index => $contact) {
            if (empty($contact['email'])) {
                continue;
            }

            $isPrimary = $index === $primaryContactIndex;

            if ($isPrimary) {
                $createdAnyPrimary = true;
            }

            $customer->contacts()->create([
                'name' => $contact['name'] ?? null,
                'email' => $contact['email'],
                'phone' => $contact['phone'] ?? null,
                'role' => $contact['role'] ?? null,
                'is_primary' => $isPrimary,
                'receives_quotes' => true,
                'receives_invoices' => false,
                'portal_access_enabled' => false,
            ]);
        }

        if (! $createdAnyPrimary) {
            $firstContact = $customer->contacts()->orderBy('id')->first();

            if ($firstContact) {
                $firstContact->update([
                    'is_primary' => true,
                ]);
            }
        }
    }
}