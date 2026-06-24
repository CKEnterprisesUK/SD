<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Invoices
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4">
        <div class="mb-8">
            <h1 class="text-3xl font-bold">Invoices</h1>
            <p class="text-gray-600 mt-2">
                View, filter and download contractor-submitted invoices.
            </p>
        </div>

        <form method="GET" action="{{ route('admin.invoices.index') }}" class="border border-gray-300 bg-white p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Filters</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="contractor_id" class="block text-sm font-semibold mb-2">
                        Contractor
                    </label>
                    <select id="contractor_id"
                            name="contractor_id"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none">
                        <option value="">All contractors</option>
                        @foreach ($contractors as $contractor)
                            <option value="{{ $contractor->id }}" @selected(($filters['contractor_id'] ?? '') == $contractor->id)>
                                {{ $contractor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-semibold mb-2">
                        Status
                    </label>
                    <select id="status"
                            name="status"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none">
                        <option value="">All statuses</option>
                        @foreach (['submitted', 'emailed', 'queried', 'cancelled', 'replaced', 'paid'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="invoice_date_from" class="block text-sm font-semibold mb-2">
                        Invoice date from
                    </label>
                    <input id="invoice_date_from"
                           name="invoice_date_from"
                           type="date"
                           value="{{ $filters['invoice_date_from'] ?? '' }}"
                           class="w-full border border-gray-400 px-4 py-3 rounded-none">
                </div>

                <div>
                    <label for="invoice_date_to" class="block text-sm font-semibold mb-2">
                        Invoice date to
                    </label>
                    <input id="invoice_date_to"
                           name="invoice_date_to"
                           type="date"
                           value="{{ $filters['invoice_date_to'] ?? '' }}"
                           class="w-full border border-gray-400 px-4 py-3 rounded-none">
                </div>

                <div>
                    <label for="week_commencing_from" class="block text-sm font-semibold mb-2">
                        Week commencing from
                    </label>
                    <input id="week_commencing_from"
                           name="week_commencing_from"
                           type="date"
                           value="{{ $filters['week_commencing_from'] ?? '' }}"
                           class="w-full border border-gray-400 px-4 py-3 rounded-none">
                </div>

                <div>
                    <label for="week_commencing_to" class="block text-sm font-semibold mb-2">
                        Week commencing to
                    </label>
                    <input id="week_commencing_to"
                           name="week_commencing_to"
                           type="date"
                           value="{{ $filters['week_commencing_to'] ?? '' }}"
                           class="w-full border border-gray-400 px-4 py-3 rounded-none">
                </div>
            </div>

            <div class="flex items-center gap-4 mt-6">
                <button type="submit"
                        class="px-5 py-3 bg-black text-white text-sm font-semibold">
                    Apply filters
                </button>

                <a href="{{ route('admin.invoices.index') }}" class="text-sm underline">
                    Clear filters
                </a>
            </div>
        </form>

        <div class="border border-gray-300 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-50 text-left">
                        <th class="px-4 py-3 font-semibold">Invoice</th>
                        <th class="px-4 py-3 font-semibold">Contractor</th>
                        <th class="px-4 py-3 font-semibold">Invoice date</th>
                        <th class="px-4 py-3 font-semibold">Week commencing</th>
                        <th class="px-4 py-3 font-semibold">Days</th>
                        <th class="px-4 py-3 font-semibold">Total</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr class="border-b border-gray-200">
                            <td class="px-4 py-3 font-semibold">
                                {{ $invoice->invoice_number }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($invoice->contractor)
                                    <a href="{{ route('admin.contractors.show', $invoice->contractor) }}" class="underline">
                                        {{ $invoice->contractor->name }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                {{ $invoice->invoice_date->format('d M Y') }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $invoice->week_commencing->format('d M Y') }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $invoice->days_worked }}
                            </td>

                            <td class="px-4 py-3">
                                £{{ $invoice->total }}
                            </td>

                            <td class="px-4 py-3">
                                {{ ucfirst($invoice->status) }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="underline">
                                        View
                                    </a>

                                    <a href="{{ route('admin.invoices.download', $invoice) }}" class="underline">
                                        Download
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-600">
                                No invoices found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $invoices->links() }}
        </div>
    </div>
</x-app-layout>