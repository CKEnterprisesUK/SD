@if ($errors->any())
    <div class="mb-6 border border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900">
        <p class="font-semibold mb-2">There is a problem with the form.</p>

        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $existingContacts = $customer
        ? $customer->contacts->map(function ($contact) {
            return [
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'role' => $contact->role,
                'is_primary' => $contact->is_primary,
            ];
        })->values()->toArray()
        : [];

    $oldContacts = old('contacts', count($existingContacts) ? $existingContacts : [
        [
            'name' => '',
            'email' => '',
            'phone' => '',
            'role' => 'Primary contact',
            'is_primary' => true,
        ],
    ]);

    $primaryContactIndex = old('primary_contact_index');

    if ($primaryContactIndex === null) {
        $primaryContactIndex = 0;

        foreach ($oldContacts as $index => $contact) {
            if (!empty($contact['is_primary'])) {
                $primaryContactIndex = $index;
                break;
            }
        }
    }
@endphp

<form method="POST" action="{{ $action }}" class="space-y-8">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <section class="border border-gray-300 bg-white p-6">
        <h2 class="text-lg font-semibold mb-4">Customer details</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-semibold mb-2">
                    Customer name <span class="text-red-700">*</span>
                </label>

                <input id="name"
                       name="name"
                       type="text"
                       value="{{ old('name', $customer?->name) }}"
                       class="w-full border border-gray-400 px-4 py-3 rounded-none"
                       required>
            </div>

            <div>
                <label for="company_name" class="block text-sm font-semibold mb-2">
                    Company name
                </label>

                <input id="company_name"
                       name="company_name"
                       type="text"
                       value="{{ old('company_name', $customer?->company_name) }}"
                       class="w-full border border-gray-400 px-4 py-3 rounded-none">
            </div>

            <div>
                <label for="status" class="block text-sm font-semibold mb-2">
                    Status <span class="text-red-700">*</span>
                </label>

                <select id="status"
                        name="status"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required>
                    <option value="active" @selected(old('status', $customer?->status ?? 'active') === 'active')>
                        Active
                    </option>

                    <option value="prospect" @selected(old('status', $customer?->status ?? 'active') === 'prospect')>
                        Prospect
                    </option>

                    <option value="inactive" @selected(old('status', $customer?->status ?? 'active') === 'inactive')>
                        Inactive
                    </option>

                    <option value="archived" @selected(old('status', $customer?->status ?? 'active') === 'archived')>
                        Archived
                    </option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label for="address" class="block text-sm font-semibold mb-2">
                    Customer / site address
                </label>

                <p class="text-xs text-gray-600 mb-2">
                    Enter the full formatted address. Address lookup can be added later when an API provider is selected.
                </p>

                <textarea id="address"
                          name="address"
                          rows="4"
                          class="w-full border border-gray-400 px-4 py-3 rounded-none"
                          placeholder="Enter the full address">{{ old('address', $customer?->address) }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label for="notes" class="block text-sm font-semibold mb-2">
                    Notes
                </label>

                <textarea id="notes"
                          name="notes"
                          rows="4"
                          class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('notes', $customer?->notes) }}</textarea>
            </div>
        </div>
    </section>

    <section class="border border-gray-300 bg-white p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
            <div>
                <h2 class="text-lg font-semibold">Contacts</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Add one or more customer contacts. Email and phone numbers are stored against contacts, not the customer record.
                </p>
            </div>

            <button type="button"
                    id="add-contact"
                    class="px-4 py-2 border border-gray-900 text-sm font-semibold">
                Add contact
            </button>
        </div>

        <div id="contacts" class="space-y-4">
            @foreach ($oldContacts as $index => $contact)
                <div class="contact-row border border-gray-300 p-4">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold mb-2">Name</label>

                            <input type="text"
                                   name="contacts[{{ $index }}][name]"
                                   value="{{ $contact['name'] ?? '' }}"
                                   class="w-full border border-gray-400 px-3 py-2 rounded-none"
                                   placeholder="e.g. Jane Smith">
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold mb-2">Email</label>

                            <input type="email"
                                   name="contacts[{{ $index }}][email]"
                                   value="{{ $contact['email'] ?? '' }}"
                                   class="w-full border border-gray-400 px-3 py-2 rounded-none"
                                   placeholder="name@example.com">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2">Phone</label>

                            <input type="text"
                                   name="contacts[{{ $index }}][phone]"
                                   value="{{ $contact['phone'] ?? '' }}"
                                   class="w-full border border-gray-400 px-3 py-2 rounded-none">
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold mb-2">Role</label>

                            <input type="text"
                                   name="contacts[{{ $index }}][role]"
                                   value="{{ $contact['role'] ?? '' }}"
                                   class="w-full border border-gray-400 px-3 py-2 rounded-none"
                                   placeholder="e.g. Homeowner, Architect, Accounts">
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-sm font-semibold mb-2">Primary</label>

                            <input type="radio"
                                   name="primary_contact_index"
                                   value="{{ $index }}"
                                   class="primary-contact-radio mt-3"
                                   @checked((int) $primaryContactIndex === $index)>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="remove-contact text-sm underline text-red-700">
                            Remove contact
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="flex items-center gap-4">
        <button type="submit"
                class="px-5 py-3 bg-black text-white text-sm font-semibold">
            {{ $customer ? 'Save customer' : 'Create customer' }}
        </button>

        <a href="{{ $customer ? route('admin.customers.show', $customer) : route('admin.customers.index') }}"
           class="text-sm underline">
            Cancel
        </a>
    </div>
</form>

<script>
    let contactIndex = document.querySelectorAll('.contact-row').length;

    const contactsContainer = document.getElementById('contacts');
    const addContactButton = document.getElementById('add-contact');

    function bindRemoveContactButtons() {
        document.querySelectorAll('.remove-contact').forEach(function (button) {
            button.onclick = function () {
                const row = button.closest('.contact-row');

                if (!row) {
                    return;
                }

                row.remove();

                const primaryRadios = document.querySelectorAll('.primary-contact-radio');

                if (primaryRadios.length && !Array.from(primaryRadios).some(radio => radio.checked)) {
                    primaryRadios[0].checked = true;
                }
            };
        });
    }

    if (addContactButton && contactsContainer) {
        addContactButton.addEventListener('click', function () {
            const html = `
                <div class="contact-row border border-gray-300 p-4">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold mb-2">Name</label>

                            <input
                                type="text"
                                name="contacts[${contactIndex}][name]"
                                class="w-full border border-gray-400 px-3 py-2 rounded-none"
                                placeholder="e.g. Jane Smith"
                            >
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold mb-2">Email</label>

                            <input
                                type="email"
                                name="contacts[${contactIndex}][email]"
                                class="w-full border border-gray-400 px-3 py-2 rounded-none"
                                placeholder="name@example.com"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2">Phone</label>

                            <input
                                type="text"
                                name="contacts[${contactIndex}][phone]"
                                class="w-full border border-gray-400 px-3 py-2 rounded-none"
                            >
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold mb-2">Role</label>

                            <input
                                type="text"
                                name="contacts[${contactIndex}][role]"
                                class="w-full border border-gray-400 px-3 py-2 rounded-none"
                                placeholder="e.g. Homeowner, Architect, Accounts"
                            >
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-sm font-semibold mb-2">Primary</label>

                            <input
                                type="radio"
                                name="primary_contact_index"
                                value="${contactIndex}"
                                class="primary-contact-radio mt-3"
                            >
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="remove-contact text-sm underline text-red-700">
                            Remove contact
                        </button>
                    </div>
                </div>
            `;

            contactsContainer.insertAdjacentHTML('beforeend', html);
            contactIndex++;

            bindRemoveContactButtons();
        });

        bindRemoveContactButtons();
    }
</script>