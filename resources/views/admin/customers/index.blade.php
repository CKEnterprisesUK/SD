<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Customers
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Customers</h1>
                <p class="text-gray-600 mt-2">
                    Manage customer records, contacts and future quote history.
                </p>
            </div>

            <a href="{{ route('admin.customers.create') }}"
               class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold">
                Add customer
            </a>
        </div>

        <form method="GET" action="{{ route('admin.customers.index') }}" class="border border-gray-300 bg-white p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <label for="search" class="block text-xs font-semibold mb-1">
                        Search
                    </label>

                    <input id="search"
                           name="search"
                           type="text"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="Customer, company, contact, email or phone"
                           class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm">
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold mb-1">
                        Status
                    </label>

                    <select id="status"
                            name="status"
                            class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm">
                        <option value="">All statuses</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="prospect" @selected(($filters['status'] ?? '') === 'prospect')>Prospect</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                        <option value="archived" @selected(($filters['status'] ?? '') === 'archived')>Archived</option>
                    </select>
                </div>

                <div class="flex items-end gap-3">
                    <button type="submit"
                            class="px-4 py-2 bg-black text-white text-sm font-semibold">
                        Apply
                    </button>

                    <a href="{{ route('admin.customers.index') }}" class="text-sm underline">
                        Clear
                    </a>
                </div>
            </div>
        </form>

        <div class="border border-gray-300 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-50 text-left">
                        <th class="px-4 py-3 font-semibold">Customer</th>
                        <th class="px-4 py-3 font-semibold">Primary contact</th>
                        <th class="px-4 py-3 font-semibold">Email</th>
                        <th class="px-4 py-3 font-semibold">Phone</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($customers as $customer)
                        @php
                            $statusClass = match ($customer->status) {
                                'active' => 'border-green-700 bg-green-50 text-green-900',
                                'prospect' => 'border-yellow-700 bg-yellow-50 text-yellow-900',
                                'inactive' => 'border-gray-500 bg-gray-50 text-gray-800',
                                'archived' => 'border-gray-400 bg-white text-gray-600',
                                default => 'border-gray-400 bg-white text-gray-700',
                            };
                        @endphp

                        <tr class="border-b border-gray-200">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-semibold underline">
                                    {{ $customer->display_name }}
                                </a>

                                @if ($customer->company_name && $customer->company_name !== $customer->name)
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $customer->name }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                {{ $customer->primaryContact?->name ?: '—' }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($customer->primaryContact?->email)
                                    <a href="mailto:{{ $customer->primaryContact->email }}" class="underline">
                                        {{ $customer->primaryContact->email }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                {{ $customer->primaryContact?->phone ?: '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 border text-xs font-semibold {{ $statusClass }}">
                                    {{ ucfirst($customer->status) }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="underline">
                                        View
                                    </a>

                                    <a href="{{ route('admin.customers.edit', $customer) }}" class="underline">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-600">
                                No customers found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>