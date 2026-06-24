<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $customer->display_name }}
            </h2>

            <a href="{{ route('admin.customers.edit', $customer) }}"
               class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold">
                Edit customer
            </a>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-8">
            <a href="{{ route('admin.customers.index') }}" class="text-sm underline">
                Back to customers
            </a>

            <h1 class="text-3xl font-bold mt-4">
                {{ $customer->display_name }}
            </h1>

            <p class="text-gray-600 mt-2">
                Customer record, contact details and future quotes.
            </p>
        </div>

        @php
            $statusClass = match ($customer->status) {
                'active' => 'border-green-700 bg-green-50 text-green-900',
                'prospect' => 'border-yellow-700 bg-yellow-50 text-yellow-900',
                'inactive' => 'border-gray-500 bg-gray-50 text-gray-800',
                'archived' => 'border-gray-400 bg-white text-gray-600',
                default => 'border-gray-400 bg-white text-gray-700',
            };
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <section class="lg:col-span-2 border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Customer details</h2>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-semibold text-gray-700">Name</dt>
                        <dd>{{ $customer->name }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Company name</dt>
                        <dd>{{ $customer->company_name ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Email</dt>
                        <dd>
                            @if ($customer->email)
                                <a href="mailto:{{ $customer->email }}" class="underline">
                                    {{ $customer->email }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Phone</dt>
                        <dd>{{ $customer->phone ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Status</dt>
                        <dd>
                            <span class="inline-flex px-2 py-1 border text-xs font-semibold {{ $statusClass }}">
                                {{ ucfirst($customer->status) }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Created</dt>
                        <dd>{{ $customer->created_at ? $customer->created_at->format('d M Y H:i') : '—' }}</dd>
                    </div>
                </dl>

                @if ($customer->address)
                    <div class="mt-6">
                        <h3 class="font-semibold text-gray-700 text-sm mb-2">Address</h3>
                        <p class="text-sm whitespace-pre-line">{{ $customer->address }}</p>
                    </div>
                @endif

                @if ($customer->notes)
                    <div class="mt-6">
                        <h3 class="font-semibold text-gray-700 text-sm mb-2">Notes</h3>
                        <p class="text-sm whitespace-pre-line">{{ $customer->notes }}</p>
                    </div>
                @endif
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Next steps</h2>

                <div class="space-y-3 text-sm">
                    <div class="border border-gray-300 bg-gray-50 p-4">
                        <p class="font-semibold">Quotes</p>
                        <p class="text-gray-600 mt-1">
                            Quote management will be added next and linked to this customer.
                        </p>
                    </div>

                    <div class="border border-gray-300 bg-gray-50 p-4">
                        <p class="font-semibold">Customer portal</p>
                        <p class="text-gray-600 mt-1">
                            Later, customers will be able to log in, submit documents and view job progress.
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <section class="border border-gray-300 bg-white p-6">
            <h2 class="text-lg font-semibold mb-4">Contacts</h2>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-300 bg-gray-50 text-left">
                            <th class="px-4 py-3 font-semibold">Contact</th>
                            <th class="px-4 py-3 font-semibold">Email</th>
                            <th class="px-4 py-3 font-semibold">Phone</th>
                            <th class="px-4 py-3 font-semibold">Role</th>
                            <th class="px-4 py-3 font-semibold">Flags</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($customer->contacts as $contact)
                            <tr class="border-b border-gray-200">
                                <td class="px-4 py-3 font-semibold">
                                    {{ $contact->name ?: '—' }}

                                    @if ($contact->is_primary)
                                        <span class="ml-2 inline-flex px-2 py-1 border border-black text-xs font-semibold">
                                            Primary
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <a href="mailto:{{ $contact->email }}" class="underline">
                                        {{ $contact->email }}
                                    </a>
                                </td>

                                <td class="px-4 py-3">
                                    {{ $contact->phone ?: '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $contact->role ?: '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if ($contact->receives_quotes)
                                            <span class="inline-flex px-2 py-1 border border-gray-500 bg-gray-50 text-xs">
                                                Quotes
                                            </span>
                                        @endif

                                        @if ($contact->receives_invoices)
                                            <span class="inline-flex px-2 py-1 border border-gray-500 bg-gray-50 text-xs">
                                                Invoices
                                            </span>
                                        @endif

                                        @if ($contact->portal_access_enabled)
                                            <span class="inline-flex px-2 py-1 border border-green-700 bg-green-50 text-green-900 text-xs">
                                                Portal
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-600">
                                    No contacts have been added yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>