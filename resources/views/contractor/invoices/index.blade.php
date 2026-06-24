<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            My Invoices
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        @if (($returnedInvoicesCount ?? 0) > 0)
            <div class="mb-6 border border-red-700 bg-red-50 px-4 py-4 text-sm text-red-900">
                <p class="font-semibold">
                    You have {{ $returnedInvoicesCount }} invoice{{ $returnedInvoicesCount === 1 ? '' : 's' }} needing your action.
                </p>

                <p class="mt-1">
                    One or more invoices have been returned by the accounts team. Open the invoice, review the comment, then edit and resubmit it.
                </p>
            </div>
        @endif

        <div class="flex items-start justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-bold">My invoices</h1>
                <p class="text-gray-600 mt-2">
                    View invoices submitted using SiteDesk.
                </p>
            </div>

            <a href="{{ route('contractor.invoices.create') }}"
               class="px-5 py-3 bg-black text-white text-sm font-semibold">
                New invoice
            </a>
        </div>

        <div class="border border-gray-300 bg-white overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-50 text-left">
                        <th class="px-4 py-3 font-semibold">Invoice number</th>
                        <th class="px-4 py-3 font-semibold">Week commencing</th>
                        <th class="px-4 py-3 font-semibold">Days</th>
                        <th class="px-4 py-3 font-semibold">Total</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Submitted</th>
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
                                'ready_for_payment' => 'Ready for payment',
                                'under_review' => 'Under review',
                                default => ucfirst(str_replace('_', ' ', $invoice->status)),
                            };
                        @endphp

                        <tr class="border-b border-gray-200 {{ $rowClass }}">
                            <td class="px-4 py-3 font-semibold">
                                {{ $invoice->invoice_number }}
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
                                {{ $invoice->submitted_at ? $invoice->submitted_at->format('d M Y H:i') : '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-3">
                                    <a href="{{ route('contractor.invoices.show', $invoice) }}" class="underline">
                                        View
                                    </a>

                                    <a href="{{ route('contractor.invoices.download', $invoice) }}" class="underline">
                                        Download
                                    </a>

                                    @if ($invoice->status === 'returned')
                                        <a href="{{ route('contractor.invoices.edit', $invoice) }}"
                                           class="font-semibold underline text-red-800">
                                            Edit and resubmit
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-600">
                                You have not submitted any invoices yet.
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