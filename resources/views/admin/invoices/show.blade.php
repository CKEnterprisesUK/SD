<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Invoice {{ $invoice->invoice_number }}
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4">
        <div class="mb-8 flex items-start justify-between gap-6">
            <div>
                <a href="{{ route('admin.invoices.index') }}" class="text-sm underline">
                    Back to invoices
                </a>

                <h1 class="text-3xl font-bold mt-4">
                    Contractor Invoice
                </h1>

                <p class="text-gray-600 mt-2">
                    {{ $invoice->invoice_number }}
                </p>
            </div>

            <a href="{{ route('admin.invoices.download', $invoice) }}"
               class="px-5 py-3 bg-black text-white text-sm font-semibold">
                Download PDF
            </a>
        </div>

        <div class="border border-gray-300 bg-yellow-50 p-4 mb-6 text-sm text-yellow-900">
            This invoice was submitted by the contractor and is locked. Do not silently edit submitted invoice data.
            If there is an issue, query, cancel or replace the invoice.
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Supplier</h2>
                <p class="font-semibold">{{ $invoice->supplier_name }}</p>
                <p class="text-sm text-gray-600">{{ $invoice->supplier_email }}</p>
                <p class="text-sm text-gray-600">{{ $invoice->supplier_phone }}</p>

                @if ($invoice->supplier_address)
                    <p class="text-sm text-gray-600 whitespace-pre-line mt-2">
                        {{ $invoice->supplier_address }}
                    </p>
                @endif

                @if ($invoice->contractor)
                    <p class="mt-4 text-sm">
                        <a href="{{ route('admin.contractors.show', $invoice->contractor) }}" class="underline">
                            View contractor profile
                        </a>
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
                    <dd>{{ ucfirst($invoice->status) }}</dd>
                </div>
            </dl>
        </section>

        <section class="border border-gray-300 bg-white p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Days submitted</h2>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                @foreach ([
                    'monday_days' => 'Monday',
                    'tuesday_days' => 'Tuesday',
                    'wednesday_days' => 'Wednesday',
                    'thursday_days' => 'Thursday',
                    'friday_days' => 'Friday',
                    'saturday_days' => 'Saturday',
                    'sunday_days' => 'Sunday',
                ] as $field => $label)
                    <div class="border border-gray-300 p-3">
                        <div class="font-semibold">{{ $label }}</div>
                        <div>{{ number_format((float) $invoice->{$field}, 2) }}</div>
                    </div>
                @endforeach
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
                    <dd>{{ $invoice->supplier_name }}</dd>
                </div>

                <div>
                    <dt class="font-semibold text-gray-700">Submitted at</dt>
                    <dd>{{ $invoice->submitted_at->format('d M Y H:i') }}</dd>
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