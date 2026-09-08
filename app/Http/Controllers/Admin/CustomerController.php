<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', 'Customer created successfully.');
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