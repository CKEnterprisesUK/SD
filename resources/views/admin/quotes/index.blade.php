<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Quotes
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
                <h1 class="text-3xl font-bold">Quotes</h1>
                <p class="text-gray-600 mt-2">
                    Manage customer quotes, site survey notes, line items and follow-up reminders.
                </p>
            </div>

            <a href="{{ route('admin.quotes.create') }}"
               class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold">
                Create quote
            </a>
        </div>

        <form method="GET" action="{{ route('admin.quotes.index') }}" class="border border-gray-300 bg-white p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-2">
                    <label for="search" class="block text-xs font-semibold mb-1">
                        Search
                    </label>

                    <input id="search"
                           name="search"
                           type="text"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="Quote number, title or customer"
                           class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm">
                </div>

                <div>
                    <label for="customer_id" class="block text-xs font-semibold mb-1">
                        Customer
                    </label>

                    <select id="customer_id"
                            name="customer_id"
                            class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm">
                        <option value="">All customers</option>

                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(($filters['customer_id'] ?? '') == $customer->id)>
                                {{ $customer->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold mb-1">
                        Status
                    </label>

                    <select id="status"
                            name="status"
                            class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm">
                        <option value="">All statuses</option>
                        <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
                        <option value="survey_in_progress" @selected(($filters['status'] ?? '') === 'survey_in_progress')>Survey in progress</option>
                        <option value="survey_completed" @selected(($filters['status'] ?? '') === 'survey_completed')>Survey completed</option>
                        <option value="ai_compiled" @selected(($filters['status'] ?? '') === 'ai_compiled')>AI compiled</option>
                        <option value="sent" @selected(($filters['status'] ?? '') === 'sent')>Sent</option>
                        <option value="accepted" @selected(($filters['status'] ?? '') === 'accepted')>Accepted</option>
                        <option value="declined" @selected(($filters['status'] ?? '') === 'declined')>Declined</option>
                        <option value="expired" @selected(($filters['status'] ?? '') === 'expired')>Expired</option>
                        <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelled</option>
                    </select>
                </div>

                <div class="flex items-end gap-3">
                    <button type="submit"
                            class="px-4 py-2 bg-black text-white text-sm font-semibold">
                        Apply
                    </button>

                    <a href="{{ route('admin.quotes.index') }}" class="text-sm underline">
                        Clear
                    </a>
                </div>
            </div>
        </form>

        <div class="border border-gray-300 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-50 text-left">
                        <th class="px-4 py-3 font-semibold">Quote</th>
                        <th class="px-4 py-3 font-semibold">Customer</th>
                        <th class="px-4 py-3 font-semibold">Assigned to</th>
                        <th class="px-4 py-3 font-semibold">Total</th>
                        <th class="px-4 py-3 font-semibold">Valid until</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($quotes as $quote)
                        @php
                            $statusClass = match ($quote->status) {
                                'draft' => 'border-gray-500 bg-gray-50 text-gray-800',
                                'survey_in_progress' => 'border-blue-700 bg-blue-50 text-blue-900',
                                'survey_completed', 'ai_compiled' => 'border-yellow-700 bg-yellow-50 text-yellow-900',
                                'sent' => 'border-purple-700 bg-purple-50 text-purple-900',
                                'accepted' => 'border-green-700 bg-green-50 text-green-900',
                                'declined', 'expired', 'cancelled' => 'border-red-700 bg-red-50 text-red-900',
                                default => 'border-gray-400 bg-white text-gray-700',
                            };

                            $statusLabel = ucfirst(str_replace('_', ' ', $quote->status));
                        @endphp

                        <tr class="border-b border-gray-200">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.quotes.show', $quote) }}" class="font-semibold underline">
                                    {{ $quote->quote_number }}
                                </a>

                                <div class="text-xs text-gray-600 mt-1">
                                    {{ $quote->title }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                @if ($quote->customer)
                                    <a href="{{ route('admin.customers.show', $quote->customer) }}" class="underline">
                                        {{ $quote->customer->display_name }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                {{ $quote->assignedUser?->name ?: '—' }}
                            </td>

                            <td class="px-4 py-3">
                                £{{ $quote->total }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $quote->valid_until ? $quote->valid_until->format('d M Y') : '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 border text-xs font-semibold {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.quotes.show', $quote) }}" class="underline">
                                        View
                                    </a>

                                    <a href="{{ route('admin.quotes.edit', $quote) }}" class="underline">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-600">
                                No quotes found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $quotes->links() }}
        </div>
    </div>
</x-app-layout>