<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            New Project
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4">
        <div class="mb-8">
            <a href="{{ route('admin.projects.index') }}" class="text-sm underline">
                Back to projects
            </a>

            <h1 class="text-2xl font-bold mt-4">New project</h1>
            <p class="text-sm text-gray-600 mt-1">
                Creating a project seeds its document library from the master folder template.
            </p>
        </div>

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

        <form method="POST" action="{{ route('admin.projects.store') }}" class="space-y-6">
            @csrf

            <section class="border border-gray-300 bg-white p-6 space-y-6">
                <div>
                    <h2 class="text-lg font-semibold">Project details</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Basic information used to identify the project.
                    </p>
                </div>

                <div>
                    <label for="name" class="block text-sm font-semibold mb-2">
                        Project name
                    </label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >
                </div>

                @php
                    $customerMode = old('customer_mode', 'existing');
                @endphp

                <input type="hidden" id="customer_mode" name="customer_mode" value="{{ $customerMode }}">

                <div>
                    <label for="customer_id" class="block text-sm font-semibold mb-2">
                        Customer
                    </label>
                    <select
                        id="customer_id"
                        name="customer_id"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                    >
                        <option value="">Select a customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) old('customer_id', $selectedCustomerId ?? '') === (string) $customer->id)>
                                {{ $customer->company_name ?: $customer->name }}
                            </option>
                        @endforeach
                        <option value="__new__" @selected($customerMode === 'new')>
                            + Add a new customer
                        </option>
                    </select>
                    <p class="text-xs text-gray-600 mt-1">
                        Choose an existing customer, or add a new one without leaving this page.
                    </p>
                </div>

                {{-- Inline "new customer" fields, revealed when "+ Add a new customer" is chosen. --}}
                <div
                    id="new-customer-fields"
                    class="border border-gray-300 bg-gray-50 p-5 space-y-5 {{ $customerMode === 'new' ? '' : 'hidden' }}"
                >
                    <div>
                        <h3 class="text-base font-semibold">New customer details</h3>
                        <p class="text-sm text-gray-600 mt-1">
                            The customer name is also saved as the main contact, so you only enter it once.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="new_customer_name" class="block text-sm font-semibold mb-2">
                                Customer name <span class="text-red-700">*</span>
                            </label>
                            <input
                                id="new_customer_name"
                                name="new_customer[name]"
                                type="text"
                                value="{{ old('new_customer.name') }}"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                placeholder="e.g. Jane Smith"
                            >
                        </div>

                        <div>
                            <label for="new_customer_company" class="block text-sm font-semibold mb-2">
                                Company name
                            </label>
                            <input
                                id="new_customer_company"
                                name="new_customer[company_name]"
                                type="text"
                                value="{{ old('new_customer.company_name') }}"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none"
                            >
                        </div>

                        <div>
                            <label for="new_customer_email" class="block text-sm font-semibold mb-2">
                                Main contact email
                            </label>
                            <input
                                id="new_customer_email"
                                name="new_customer[email]"
                                type="email"
                                value="{{ old('new_customer.email') }}"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                placeholder="name@example.com"
                            >
                        </div>

                        <div>
                            <label for="new_customer_phone" class="block text-sm font-semibold mb-2">
                                Main contact phone
                            </label>
                            <input
                                id="new_customer_phone"
                                name="new_customer[phone]"
                                type="text"
                                value="{{ old('new_customer.phone') }}"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none"
                            >
                        </div>

                        <div>
                            <label for="new_customer_role" class="block text-sm font-semibold mb-2">
                                Main contact role
                            </label>
                            <input
                                id="new_customer_role"
                                name="new_customer[role]"
                                type="text"
                                value="{{ old('new_customer.role', 'Primary contact') }}"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                placeholder="e.g. Homeowner, Director"
                            >
                        </div>

                        <div>
                            <label for="new_customer_status" class="block text-sm font-semibold mb-2">
                                Status <span class="text-red-700">*</span>
                            </label>
                            <select
                                id="new_customer_status"
                                name="new_customer[status]"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none"
                            >
                                @foreach (['active' => 'Active', 'prospect' => 'Prospect', 'inactive' => 'Inactive', 'archived' => 'Archived'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('new_customer.status', 'active') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label for="new_customer_address" class="block text-sm font-semibold mb-2">
                                Customer / site address
                            </label>
                            <textarea
                                id="new_customer_address"
                                name="new_customer[address]"
                                rows="3"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                placeholder="Enter the full address"
                            >{{ old('new_customer.address') }}</textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label for="new_customer_notes" class="block text-sm font-semibold mb-2">
                                Notes
                            </label>
                            <textarea
                                id="new_customer_notes"
                                name="new_customer[notes]"
                                rows="3"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none"
                            >{{ old('new_customer.notes') }}</textarea>
                        </div>

                        <div class="md:col-span-2 border border-gray-300 bg-white p-4">
                            <label class="flex items-start gap-3 text-sm">
                                <input
                                    type="checkbox"
                                    name="new_customer[invite_to_portal]"
                                    value="1"
                                    class="mt-1"
                                    @checked(old('new_customer.invite_to_portal'))
                                >
                                <span>
                                    <span class="font-semibold">Invite the main contact to the Green Street Portal</span>
                                    <span class="block text-gray-600 mt-1">
                                        Sends the main contact a password-setup email so they can sign in and view their project documents. Requires a main contact email above. You can also send this later from the customer dashboard.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="reference" class="block text-sm font-semibold mb-2">
                        Reference
                    </label>
                    <input
                        id="reference"
                        name="reference"
                        type="text"
                        value="{{ old('reference') }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                    >
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold mb-2">
                        Description
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                    >{{ old('description') }}</textarea>
                </div>
            </section>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Create project
                </button>
                <a href="{{ route('admin.projects.index') }}" class="text-sm underline">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const select = document.getElementById('customer_id');
            const modeInput = document.getElementById('customer_mode');
            const panel = document.getElementById('new-customer-fields');

            if (!select || !modeInput || !panel) {
                return;
            }

            const newNameInput = document.getElementById('new_customer_name');
            const newStatusInput = document.getElementById('new_customer_status');

            function applyMode() {
                const creatingNew = select.value === '__new__';

                modeInput.value = creatingNew ? 'new' : 'existing';
                panel.classList.toggle('hidden', !creatingNew);

                // Only require the inline fields (and drop the customer_id
                // requirement) when creating a new customer, so browser
                // validation matches what the server expects.
                if (newNameInput) {
                    newNameInput.required = creatingNew;
                }
                if (newStatusInput) {
                    newStatusInput.required = creatingNew;
                }

                select.required = !creatingNew;
            }

            select.addEventListener('change', applyMode);

            // Restore the correct state on load (e.g. after a validation error).
            applyMode();
        })();
    </script>
</x-app-layout>
