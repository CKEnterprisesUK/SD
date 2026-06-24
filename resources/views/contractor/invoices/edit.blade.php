<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Invoice {{ $invoice->invoice_number }}
                </h2>
            </div>

            <div>
                <a href="{{ route('contractor.invoices.show', $invoice) }}"
                   class="inline-flex px-5 py-3 border border-gray-900 text-gray-900 text-sm font-semibold">
                    Back to invoice
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="mb-8">
            <h1 class="text-3xl font-bold">
                Edit contractor invoice
            </h1>

            <p class="text-gray-600 mt-2">
                This invoice was returned by the accounts team. Make the requested changes and resubmit it for review.
            </p>
        </div>

        @if ($invoice->review_comment)
            <div class="mb-6 border border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900">
                <p class="font-semibold mb-2">Accounts comment</p>
                <p class="whitespace-pre-line">{{ $invoice->review_comment }}</p>
            </div>
        @endif

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

        <form method="POST" action="{{ route('contractor.invoices.update', $invoice) }}" class="space-y-8">
            @csrf
            @method('PUT')

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Invoice details</h2>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm mb-6">
                    <div>
                        <dt class="font-semibold text-gray-700">Invoice number</dt>
                        <dd>{{ $invoice->invoice_number }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Invoice date</dt>
                        <dd>{{ $invoice->invoice_date->format('d M Y') }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Week commencing</dt>
                        <dd>{{ $invoice->week_commencing->format('d M Y') }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Current status</dt>
                        <dd>{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</dd>
                    </div>
                </dl>

                <div>
                    <label for="day_rate" class="block text-sm font-semibold mb-2">
                        Day rate
                    </label>

                    <div class="flex">
                        <span class="inline-flex items-center border border-r-0 border-gray-400 px-4 bg-gray-50">
                            £
                        </span>

                        <input
                            id="day_rate"
                            name="day_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            value="{{ old('day_rate', $invoice->actual_day_rate) }}"
                            data-default-rate="{{ $contractor->day_rate }}"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                            required
                        >
                    </div>

                    <p class="text-sm text-gray-600 mt-1">
                        Your default day rate is £{{ $contractor->day_rate }}.
                    </p>

                    <div id="day-rate-warning"
                         class="hidden mt-3 border border-yellow-700 bg-yellow-50 px-4 py-3 text-sm text-yellow-900">
                        You have changed the day rate from the default rate on your contractor profile.
                        This will be recorded on the invoice.
                    </div>
                </div>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Days worked</h2>

                <p class="text-sm text-gray-600 mb-4">
                    Select how much of each day you worked.
                </p>

                <div class="space-y-3">
                    @foreach ([
                        'monday_days' => 'Monday',
                        'tuesday_days' => 'Tuesday',
                        'wednesday_days' => 'Wednesday',
                        'thursday_days' => 'Thursday',
                        'friday_days' => 'Friday',
                        'saturday_days' => 'Saturday',
                        'sunday_days' => 'Sunday',
                    ] as $field => $label)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center border border-gray-300 p-4">
                            <label for="{{ $field }}" class="font-semibold">
                                {{ $label }}
                            </label>

                            <select
                                id="{{ $field }}"
                                name="{{ $field }}"
                                class="sm:col-span-2 w-full border border-gray-400 px-4 py-3 rounded-none"
                                required
                            >
                                @foreach ([
                                    '0' => 'Not worked',
                                    '0.25' => 'Quarter day',
                                    '0.5' => 'Half day',
                                    '0.75' => 'Three-quarter day',
                                    '1' => 'Full day',
                                ] as $value => $text)
                                    <option
                                        value="{{ $value }}"
                                        @selected((string) old($field, rtrim(rtrim(number_format((float) $invoice->{$field}, 2, '.', ''), '0'), '.')) === (string) $value)
                                    >
                                        {{ $text }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Additional line items</h2>

                <p class="text-sm text-gray-600 mb-4">
                    Add agreed materials, expenses or other costs for this invoice.
                </p>

                @php
                    $existingLineItems = $invoice->lineItems->map(function ($lineItem) {
                        return [
                            'type' => $lineItem->type,
                            'description' => $lineItem->description,
                            'quantity' => $lineItem->quantity,
                            'unit_amount' => $lineItem->unit_amount,
                        ];
                    })->values()->toArray();

                    $oldLineItems = old('line_items', count($existingLineItems) ? $existingLineItems : [
                        [
                            'type' => 'materials',
                            'description' => '',
                            'quantity' => '1',
                            'unit_amount' => '',
                        ],
                    ]);
                @endphp

                <div id="line-items" class="space-y-4">
                    @foreach ($oldLineItems as $index => $lineItem)
                        <div class="line-item grid grid-cols-1 md:grid-cols-12 gap-3 border border-gray-300 p-4">
                            <div class="md:col-span-3">
                                <label class="block text-sm font-semibold mb-2">
                                    Type
                                </label>

                                <select
                                    name="line_items[{{ $index }}][type]"
                                    class="w-full border border-gray-400 px-3 py-2 rounded-none"
                                >
                                    <option value="materials" @selected(($lineItem['type'] ?? '') === 'materials')>
                                        Materials
                                    </option>
                                    <option value="expense" @selected(($lineItem['type'] ?? '') === 'expense')>
                                        Expense
                                    </option>
                                    <option value="plant_hire" @selected(($lineItem['type'] ?? '') === 'plant_hire')>
                                        Plant hire
                                    </option>
                                    <option value="other" @selected(($lineItem['type'] ?? '') === 'other')>
                                        Other
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-span-4">
                                <label class="block text-sm font-semibold mb-2">
                                    Description
                                </label>

                                <input
                                    type="text"
                                    name="line_items[{{ $index }}][description]"
                                    value="{{ $lineItem['description'] ?? '' }}"
                                    class="w-full border border-gray-400 px-3 py-2 rounded-none"
                                    placeholder="e.g. Timber, fixings, parking"
                                >
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold mb-2">
                                    Quantity
                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="line_items[{{ $index }}][quantity]"
                                    value="{{ $lineItem['quantity'] ?? '1' }}"
                                    class="w-full border border-gray-400 px-3 py-2 rounded-none"
                                >
                            </div>

                            <div class="md:col-span-3">
                                <label class="block text-sm font-semibold mb-2">
                                    Unit amount
                                </label>

                                <div class="flex">
                                    <span class="inline-flex items-center border border-r-0 border-gray-400 px-3 bg-gray-50">
                                        £
                                    </span>

                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="line_items[{{ $index }}][unit_amount]"
                                        value="{{ $lineItem['unit_amount'] ?? '' }}"
                                        class="w-full border border-gray-400 px-3 py-2 rounded-none"
                                    >
                                </div>
                            </div>

                            <div class="md:col-span-12">
                                <button
                                    type="button"
                                    class="remove-line-item text-sm underline text-red-700"
                                >
                                    Remove line
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button
                    type="button"
                    id="add-line-item"
                    class="mt-4 px-4 py-2 border border-gray-900 text-sm font-semibold"
                >
                    Add another line
                </button>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Notes</h2>

                <label for="contractor_notes" class="block text-sm font-semibold mb-2">
                    Contractor notes, if any
                </label>

                <textarea
                    id="contractor_notes"
                    name="contractor_notes"
                    rows="4"
                    class="w-full border border-gray-400 px-4 py-3 rounded-none"
                >{{ old('contractor_notes', $invoice->contractor_notes) }}</textarea>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Confirmation</h2>

                <div class="border border-gray-300 bg-gray-50 p-4 text-sm mb-4">
                    I confirm this invoice is accurate and is being resubmitted by me to
                    {{ $settings->company_name ?: $settings->portal_name ?: 'the building firm' }}
                    for review.
                </div>

                <label class="flex items-start gap-3">
                    <input
                        type="checkbox"
                        name="confirm_submission"
                        value="1"
                        class="mt-1"
                        required
                    >

                    <span class="text-sm">
                        I confirm and want to resubmit this invoice.
                    </span>
                </label>
            </section>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    class="px-5 py-3 bg-black text-white text-sm font-semibold"
                >
                    Resubmit invoice
                </button>

                <a href="{{ route('contractor.invoices.show', $invoice) }}" class="text-sm underline">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        const dayRateInput = document.getElementById('day_rate');
        const warning = document.getElementById('day-rate-warning');

        if (dayRateInput && warning) {
            const defaultRate = Number(dayRateInput.dataset.defaultRate);

            function checkDayRateWarning() {
                const currentRate = Number(dayRateInput.value);

                if (currentRate !== defaultRate) {
                    warning.classList.remove('hidden');
                } else {
                    warning.classList.add('hidden');
                }
            }

            dayRateInput.addEventListener('input', checkDayRateWarning);
            checkDayRateWarning();
        }

        let lineItemIndex = document.querySelectorAll('.line-item').length;

        const addLineItemButton = document.getElementById('add-line-item');
        const lineItemsContainer = document.getElementById('line-items');

        function bindRemoveLineItemButtons() {
            document.querySelectorAll('.remove-line-item').forEach(function (button) {
                button.onclick = function () {
                    const lineItem = button.closest('.line-item');

                    if (lineItem) {
                        lineItem.remove();
                    }
                };
            });
        }

        if (addLineItemButton && lineItemsContainer) {
            addLineItemButton.addEventListener('click', function () {
                const html = `
                    <div class="line-item grid grid-cols-1 md:grid-cols-12 gap-3 border border-gray-300 p-4">
                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold mb-2">
                                Type
                            </label>

                            <select
                                name="line_items[${lineItemIndex}][type]"
                                class="w-full border border-gray-400 px-3 py-2 rounded-none"
                            >
                                <option value="materials">Materials</option>
                                <option value="expense">Expense</option>
                                <option value="plant_hire">Plant hire</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-sm font-semibold mb-2">
                                Description
                            </label>

                            <input
                                type="text"
                                name="line_items[${lineItemIndex}][description]"
                                class="w-full border border-gray-400 px-3 py-2 rounded-none"
                                placeholder="e.g. Timber, fixings, parking"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2">
                                Quantity
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="line_items[${lineItemIndex}][quantity]"
                                value="1"
                                class="w-full border border-gray-400 px-3 py-2 rounded-none"
                            >
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold mb-2">
                                Unit amount
                            </label>

                            <div class="flex">
                                <span class="inline-flex items-center border border-r-0 border-gray-400 px-3 bg-gray-50">
                                    £
                                </span>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="line_items[${lineItemIndex}][unit_amount]"
                                    class="w-full border border-gray-400 px-3 py-2 rounded-none"
                                >
                            </div>
                        </div>

                        <div class="md:col-span-12">
                            <button
                                type="button"
                                class="remove-line-item text-sm underline text-red-700"
                            >
                                Remove line
                            </button>
                        </div>
                    </div>
                `;

                lineItemsContainer.insertAdjacentHTML('beforeend', html);
                lineItemIndex++;

                bindRemoveLineItemButtons();
            });

            bindRemoveLineItemButtons();
        }
    </script>
</x-app-layout>