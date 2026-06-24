<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Invoices
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4">
        <div class="mb-4">
            <h1 class="text-3xl font-bold">Invoices</h1>
            <p class="text-gray-600 mt-2">
                View, filter and download contractor-submitted invoices.
            </p>
        </div>

        @php
            $money = fn ($pence) => '£' . number_format(($pence ?? 0) / 100, 2);
        @endphp

        <div class="mb-4 overflow-x-auto">
            <div style="display: flex; gap: 10px; align-items: stretch; justify-content: flex-start; min-width: 760px;">
                <a href="{{ route('admin.invoices.index', array_merge(request()->except('status', 'page'), ['status' => 'awaiting_review'])) }}"
                   style="width: 150px; min-height: 66px;"
                   class="border border-gray-500 bg-gray-50 px-3 py-2 no-underline">
                    <div class="text-xs font-semibold text-gray-700 truncate">
                        Awaiting review
                    </div>

                    <div class="flex items-end justify-between gap-2 mt-2">
                        <div class="text-xs text-gray-600 truncate">
                            {{ $money($summary['awaiting_review']['total_pence'] ?? 0) }}
                        </div>

                        <div class="text-2xl font-bold text-gray-900 leading-none">
                            {{ $summary['awaiting_review']['count'] ?? 0 }}
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.invoices.index', array_merge(request()->except('status', 'page'), ['status' => 'returned'])) }}"
                   style="width: 150px; min-height: 66px;"
                   class="border border-red-700 bg-red-50 px-3 py-2 no-underline">
                    <div class="text-xs font-semibold text-red-900 truncate">
                        Returned
                    </div>

                    <div class="flex items-end justify-between gap-2 mt-2">
                        <div class="text-xs text-red-900 truncate">
                            {{ $money($summary['returned']['total_pence'] ?? 0) }}
                        </div>

                        <div class="text-2xl font-bold text-red-900 leading-none">
                            {{ $summary['returned']['count'] ?? 0 }}
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.invoices.index', array_merge(request()->except('status', 'page'), ['status' => 'ready_or_paid'])) }}"
                   style="width: 150px; min-height: 66px;"
                   class="border border-green-700 bg-green-50 px-3 py-2 no-underline">
                    <div class="text-xs font-semibold text-green-900 truncate">
                        Sent / paid
                    </div>

                    <div class="flex items-end justify-between gap-2 mt-2">
                        <div class="text-xs text-green-900 truncate">
                            {{ $money($summary['ready_or_paid']['total_pence'] ?? 0) }}
                        </div>

                        <div class="text-2xl font-bold text-green-900 leading-none">
                            {{ $summary['ready_or_paid']['count'] ?? 0 }}
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.invoices.index', array_merge(request()->except('status', 'page'), ['status' => 'cancelled_or_replaced'])) }}"
                   style="width: 150px; min-height: 66px;"
                   class="border border-gray-400 bg-white px-3 py-2 no-underline">
                    <div class="text-xs font-semibold text-gray-700 truncate">
                        Cancelled / replaced
                    </div>

                    <div class="flex items-end justify-between gap-2 mt-2">
                        <div class="text-xs text-gray-600 truncate">
                            {{ $money($summary['cancelled_or_replaced']['total_pence'] ?? 0) }}
                        </div>

                        <div class="text-2xl font-bold text-gray-900 leading-none">
                            {{ $summary['cancelled_or_replaced']['count'] ?? 0 }}
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.invoices.index', request()->except('status', 'page')) }}"
                   style="width: 150px; min-height: 66px;"
                   class="border border-black bg-white px-3 py-2 no-underline">
                    <div class="text-xs font-semibold text-gray-700 truncate">
                        Total invoices
                    </div>

                    <div class="flex items-end justify-between gap-2 mt-2">
                        <div class="text-xs text-gray-600 truncate">
                            {{ $money($summary['all']['total_pence'] ?? 0) }}
                        </div>

                        <div class="text-2xl font-bold text-gray-900 leading-none">
                            {{ $summary['all']['count'] ?? 0 }}
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.invoices.index') }}" class="border border-gray-300 bg-white p-3 mb-4">
            <div class="flex items-center justify-between gap-4 mb-3">
                <h2 class="text-sm font-semibold">Filters</h2>

                <a href="{{ route('admin.invoices.index') }}" class="text-xs underline">
                    Clear filters
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2">
                <div>
                    <label for="contractor_id" class="block text-xs font-semibold mb-1">
                        Contractor
                    </label>

                    <select id="contractor_id"
                            name="contractor_id"
                            class="w-full border border-gray-400 px-2 py-1.5 rounded-none text-xs">
                        <option value="">All contractors</option>

                        @foreach ($contractors as $contractor)
                            <option value="{{ $contractor->id }}" @selected(($filters['contractor_id'] ?? '') == $contractor->id)>
                                {{ $contractor->name }}
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
                            class="w-full border border-gray-400 px-2 py-1.5 rounded-none text-xs">
                        <option value="">All statuses</option>

                        <option value="awaiting_review" @selected(($filters['status'] ?? '') === 'awaiting_review')>
                            Awaiting review
                        </option>

                        <option value="submitted" @selected(($filters['status'] ?? '') === 'submitted')>
                            Submitted
                        </option>

                        <option value="resubmitted" @selected(($filters['status'] ?? '') === 'resubmitted')>
                            Resubmitted
                        </option>

                        <option value="under_review" @selected(($filters['status'] ?? '') === 'under_review')>
                            Under review
                        </option>

                        <option value="returned" @selected(($filters['status'] ?? '') === 'returned')>
                            Returned
                        </option>

                        <option value="ready_or_paid" @selected(($filters['status'] ?? '') === 'ready_or_paid')>
                            Sent / paid
                        </option>

                        <option value="ready_for_payment" @selected(($filters['status'] ?? '') === 'ready_for_payment')>
                            Sent for payment
                        </option>

                        <option value="paid" @selected(($filters['status'] ?? '') === 'paid')>
                            Paid
                        </option>

                        <option value="cancelled_or_replaced" @selected(($filters['status'] ?? '') === 'cancelled_or_replaced')>
                            Cancelled / replaced
                        </option>

                        <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>
                            Cancelled
                        </option>

                        <option value="replaced" @selected(($filters['status'] ?? '') === 'replaced')>
                            Replaced
                        </option>
                    </select>
                </div>

                <div>
                    <label for="invoice_date_from" class="block text-xs font-semibold mb-1">
                        Invoice from
                    </label>

                    <input id="invoice_date_from"
                           name="invoice_date_from"
                           type="date"
                           value="{{ $filters['invoice_date_from'] ?? '' }}"
                           class="w-full border border-gray-400 px-2 py-1.5 rounded-none text-xs">
                </div>

                <div>
                    <label for="invoice_date_to" class="block text-xs font-semibold mb-1">
                        Invoice to
                    </label>

                    <input id="invoice_date_to"
                           name="invoice_date_to"
                           type="date"
                           value="{{ $filters['invoice_date_to'] ?? '' }}"
                           class="w-full border border-gray-400 px-2 py-1.5 rounded-none text-xs">
                </div>

                <div>
                    <label for="week_commencing_from" class="block text-xs font-semibold mb-1">
                        Week from
                    </label>

                    <input id="week_commencing_from"
                           name="week_commencing_from"
                           type="date"
                           value="{{ $filters['week_commencing_from'] ?? '' }}"
                           class="w-full border border-gray-400 px-2 py-1.5 rounded-none text-xs">
                </div>

                <div>
                    <label for="week_commencing_to" class="block text-xs font-semibold mb-1">
                        Week to
                    </label>

                    <input id="week_commencing_to"
                           name="week_commencing_to"
                           type="date"
                           value="{{ $filters['week_commencing_to'] ?? '' }}"
                           class="w-full border border-gray-400 px-2 py-1.5 rounded-none text-xs">
                </div>
            </div>

            <div class="flex items-center gap-3 mt-3">
                <button type="submit"
                        class="px-3 py-1.5 bg-black text-white text-xs font-semibold">
                    Apply
                </button>
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
                        @php
                            $rowClass = match ($invoice->status) {
                                'returned' => 'bg-red-50',
                                'ready_for_payment', 'paid' => 'bg-green-50',
                                'submitted', 'resubmitted', 'under_review' => 'bg-gray-50',
                                default => 'bg-white',
                            };

                            $statusClass = match ($invoice->status) {
                                'returned' => 'border-red-700 bg-red-50 text-red-900',
                                'ready_for_payment', 'paid' => 'border-green-700 bg-green-50 text-green-900',
                                'submitted', 'resubmitted', 'under_review' => 'border-gray-500 bg-gray-50 text-gray-800',
                                default => 'border-gray-400 bg-white text-gray-700',
                            };

                            $statusLabel = match ($invoice->status) {
                                'ready_for_payment' => 'Sent for payment',
                                'under_review' => 'Under review',
                                default => ucfirst(str_replace('_', ' ', $invoice->status)),
                            };
                        @endphp

                        <tr class="border-b border-gray-200 {{ $rowClass }}">
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
                                <span class="inline-flex px-2 py-1 border text-xs font-semibold {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
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