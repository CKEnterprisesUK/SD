<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            My Invoices
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4">
        <div class="flex items-start justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-bold">My Invoices</h1>
                <p class="text-gray-600 mt-2">
                    View invoices you have submitted using SiteDesk.
                </p>
            </div>

            <a href="{{ route('contractor.invoices.create') }}"
               class="px-5 py-3 bg-black text-white text-sm font-semibold">
                New invoice
            </a>
        </div>

        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

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
                        <tr class="border-b border-gray-200">
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
                                {{ ucfirst($invoice->status) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $invoice->submitted_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('contractor.invoices.show', $invoice) }}" class="underline">
                                    View
                                </a>
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