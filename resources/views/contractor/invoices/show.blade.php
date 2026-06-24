<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Invoice {{ $invoice->invoice_number }}
                </h2>
            </div>

            <div>
                <a href="{{ route('contractor.invoices.download', $invoice) }}"
                   class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold">
                    Download PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-8">
            <a href="{{ route('contractor.invoices.index') }}" class="text-sm underline">
                Back to my invoices
            </a>

            <h1 class="text-3xl font-bold mt-4">
                Contractor invoice
            </h1>

            <p class="text-gray-600 mt-2">
                {{ $invoice->invoice_number }}
            </p>
        </div>

        @if ($invoice->status === 'returned')
            <div class="border border-red-700 bg-red-50 p-4 mb-6 text-sm text-red-900">
                <p class="font-semibold mb-2">This invoice has been returned for changes.</p>

                @if ($invoice->review_comment)
                    <p class="whitespace-pre-line">{{ $invoice->review_comment }}</p>
                @endif

                <p class="mt-3">
                    You will be able to edit and resubmit this invoice once the edit flow has been added.
                </p>
            </div>
        @elseif (in_array($invoice->status, ['submitted', 'resubmitted']))
            <div class="border border-yellow-700 bg-yellow-50 p-4 mb-6 text-sm text-yellow-900">
                This invoice has been submitted and is awaiting review by the accounts team.
            </div>
        @elseif ($invoice->status === 'ready_for_payment')
            <div class="border border-green-700 bg-green-50 p-4 mb-6 text-sm text-green-900">
                This invoice has been reviewed and marked as ready for payment.
            </div>
        @else
            <div class="border border-gray-300 bg-gray-50 p-4 mb-6 text-sm text-gray-700">
                This invoice has been submitted and locked. If something is wrong,
                contact the accounts team.
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Supplier</h2>

                <p class="font-semibold">{{ $invoice->supplier_name }}</p>
                <p class="text-sm text-gray-600">{{ $invoice->supplier_email ?: '—' }}</p>
                <p class="text-sm text-gray-600">{{ $invoice->supplier_phone ?: '—' }}</p>

                @if ($invoice->supplier_address)
                    <p class="text-sm text-gray-600 whitespace-pre-line mt-2">
                        {{ $invoice->supplier_address }}
                    </p>
                @endif
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Customer</h2>

                <p class="font-semibold">{{ $invoice->customer_name ?: '—' }}</p>

                @if ($invoice->customer_address)
                    <p class="text-sm text-gray-600 whitespace-pre-line mt-2">
                        {{ $invoice->customer_address }}
                    </p>
                @endif
            </section>
        </div>

        <section class="border border-gray-300 bg-white p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Invoice details</h2>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="font-semibold text-gray-700">Invoice date</dt>
                    <dd>{{ $invoice->invoice_date->format('d M Y') }}</dd>
                </div>

                <div>
                    <dt class="font-semibold text-gray-700">Week commencing</dt>
                    <dd>{{ $invoice->week_commencing->format('d M Y') }}</dd>
                </div>

                <div>
                    <dt class="font-semibold text-gray-700">Days worked</dt>
                    <dd>{{ $invoice->days_worked }}</dd>
                </div>

                <div>
                    <dt class="font-semibold text-gray-700">Day rate</dt>
                    <dd>
                        £{{ $invoice->actual_day_rate }}

                        @if ($invoice->day_rate_overridden)
                            <span class="ml-2 text-xs border border-yellow-700 bg-yellow-50 text-yellow-900 px-2 py-1">
                                Manually overridden
                            </span>
                        @endif
                    </dd>
                </div>

                <div>
                    <dt class="font-semibold text-gray-700">Subtotal</dt>
                    <dd>£{{ $invoice->subtotal }}</dd>
                </div>

                <div>
                    <dt class="font-semibold text-gray-700">VAT</dt>
                    <dd>£{{ $invoice->vat }}</dd>
                </div>

                <div>
                    <dt class="font-semibold text-gray-700">Total due</dt>
                    <dd class="font-bold">£{{ $invoice->total }}</dd>
                </div>

                <div>
                    <dt class="font-semibold text-gray-700">Status</dt>
                    <dd>{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</dd>
                </div>
            </dl>
        </section>

        <section class="border border-gray-300 bg-white p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Days selected</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                @foreach ([
                    'monday_days' => 'Monday',
                    'tuesday_days' => 'Tuesday',
                    'wednesday_days' => 'Wednesday',
                    'thursday_days' => 'Thursday',
                    'friday_days' => 'Friday',
                    'saturday_days' => 'Saturday',
                    'sunday_days' => 'Sunday',
                ] as $field => $label)
                    @php
                        $dayValue = (float) $invoice->{$field};

                        $dayText = match (true) {
                            $dayValue === 1.0 => 'Full day',
                            $dayValue === 0.75 => 'Three-quarter day',
                            $dayValue === 0.5 => 'Half day',
                            $dayValue === 0.25 => 'Quarter day',
                            default => 'Not worked',
                        };
                    @endphp

                    <div class="border border-gray-300 p-3 {{ $dayValue > 0 ? 'bg-gray-100 font-semibold' : 'text-gray-400' }}">
                        <div>{{ $label }}</div>
                        <div class="text-sm {{ $dayValue > 0 ? 'text-gray-700' : 'text-gray-400' }}">
                            {{ $dayText }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @if ($invoice->lineItems->count())
            <section class="border border-gray-300 bg-white p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Additional line items</h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-300 bg-gray-50 text-left">
                                <th class="px-4 py-3 font-semibold">Type</th>
                                <th class="px-4 py-3 font-semibold">Description</th>
                                <th class="px-4 py-3 font-semibold text-right">Quantity</th>
                                <th class="px-4 py-3 font-semibold text-right">Unit amount</th>
                                <th class="px-4 py-3 font-semibold text-right">Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($invoice->lineItems as $lineItem)
                                <tr class="border-b border-gray-200">
                                    <td class="px-4 py-3">
                                        {{ ucfirst(str_replace('_', ' ', $lineItem->type)) }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $lineItem->description }}
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        {{ $lineItem->quantity }}
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        £{{ $lineItem->unit_amount }}
                                    </td>

                                    <td class="px-4 py-3 text-right font-semibold">
                                        £{{ $lineItem->total }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="border border-gray-300 bg-white p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Totals</h2>

            <div class="max-w-sm ml-auto text-sm">
                <div class="flex items-center justify-between border-b border-gray-200 py-2">
                    <span>Subtotal</span>
                    <span>£{{ $invoice->subtotal }}</span>
                </div>

                <div class="flex items-center justify-between border-b border-gray-200 py-2">
                    <span>VAT</span>
                    <span>£{{ $invoice->vat }}</span>
                </div>

                <div class="flex items-center justify-between py-3 text-lg font-bold">
                    <span>Total due</span>
                    <span>£{{ $invoice->total }}</span>
                </div>
            </div>
        </section>

        @if ($invoice->contractor_notes)
            <section class="border border-gray-300 bg-white p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Contractor notes</h2>
                <p class="text-sm whitespace-pre-line">{{ $invoice->contractor_notes }}</p>
            </section>
        @endif

        <section class="border border-gray-300 bg-white p-6">
            <h2 class="text-lg font-semibold mb-4">Submission record</h2>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="font-semibold text-gray-700">Submitted by</dt>
                    <dd>{{ $contractor->name }}</dd>
                </div>

                <div>
                    <dt class="font-semibold text-gray-700">Submitted at</dt>
                    <dd>{{ $invoice->submitted_at ? $invoice->submitted_at->format('d M Y H:i') : 'Not recorded' }}</dd>
                </div>

                <div>
                    <dt class="font-semibold text-gray-700">IP address</dt>
                    <dd>{{ $invoice->submitted_ip ?: 'Not recorded' }}</dd>
                </div>

                <div>
                    <dt class="font-semibold text-gray-700">VAT status</dt>
                    <dd>This is not a VAT invoice. VAT has not been charged.</dd>
                </div>
            </dl>

            <div class="mt-4 border border-gray-300 bg-gray-50 p-4 text-sm">
                {{ $invoice->contractor_confirmation_text }}
            </div>
        </section>
    </div>
</x-app-layout>