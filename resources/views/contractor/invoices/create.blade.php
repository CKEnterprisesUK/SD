<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Submit Invoice
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="mb-8">
            <a href="{{ route('contractor.invoices.index') }}" class="text-sm underline">
                Back to my invoices
            </a>

            <h1 class="text-3xl font-bold mt-4">Submit contractor invoice</h1>
            <p class="text-gray-600 mt-2">
                Complete your weekly invoice and submit it for payment.
            </p>
        </div>

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

        <form method="POST" action="{{ route('contractor.invoices.store') }}" class="space-y-8">
            @csrf

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Invoice details</h2>

                <div class="space-y-5">
                    <div>
                        <label for="week_commencing" class="block text-sm font-semibold mb-2">
                            Week commencing
                        </label>

                        <input
                            id="week_commencing"
                            name="week_commencing"
                            type="date"
                            value="{{ old('week_commencing') }}"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                            required
                        >

                        <p class="text-sm text-gray-600 mt-1">
                            This will automatically use the Monday of the selected week.
                        </p>
                    </div>

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
                                value="{{ old('day_rate', $contractor->day_rate) }}"
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
                </div>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Days worked</h2>

                <p class="text-sm text-gray-600 mb-4">
                    Select how much of each day you worked.
                </p>

                <div class="space-y-3">
                    @foreach ([
                        'monday_days' => ['label' => 'Monday', 'offset' => 0],
                        'tuesday_days' => ['label' => 'Tuesday', 'offset' => 1],
                        'wednesday_days' => ['label' => 'Wednesday', 'offset' => 2],
                        'thursday_days' => ['label' => 'Thursday', 'offset' => 3],
                        'friday_days' => ['label' => 'Friday', 'offset' => 4],
                        'saturday_days' => ['label' => 'Saturday', 'offset' => 5],
                        'sunday_days' => ['label' => 'Sunday', 'offset' => 6],
                    ] as $field => $day)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center border border-gray-300 p-4">
                            <label for="{{ $field }}" class="font-semibold">
                                <span>{{ $day['label'] }}</span>
                                <span
                                    id="{{ $field }}_date"
                                    class="block text-sm font-normal text-gray-600 mt-1"
                                    data-day-offset="{{ $day['offset'] }}"
                                >
                                    —
                                </span>
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
                                    <option value="{{ $value }}" @selected(old($field, '0') == $value)>
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

    <div id="line-items" class="space-y-4">
        @php
            $oldLineItems = old('line_items', [
                [
                    'type' => 'materials',
                    'description' => '',
                    'quantity' => '1',
                    'unit_amount' => '',
                ],
            ]);
        @endphp

        @foreach ($oldLineItems as $index => $lineItem)
            <div class="line-item grid grid-cols-1 md:grid-cols-12 gap-3 border border-gray-300 p-4">
                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold mb-2">Type</label>

                    <select
                        name="line_items[{{ $index }}][type]"
                        class="w-full border border-gray-400 px-3 py-2 rounded-none"
                    >
                        <option value="materials" @selected(($lineItem['type'] ?? '') === 'materials')>Materials</option>
                        <option value="expense" @selected(($lineItem['type'] ?? '') === 'expense')>Expense</option>
                        <option value="plant_hire" @selected(($lineItem['type'] ?? '') === 'plant_hire')>Plant hire</option>
                        <option value="other" @selected(($lineItem['type'] ?? '') === 'other')>Other</option>
                    </select>
                </div>

                <div class="md:col-span-4">
                    <label class="block text-sm font-semibold mb-2">Description</label>

                    <input
                        type="text"
                        name="line_items[{{ $index }}][description]"
                        value="{{ $lineItem['description'] ?? '' }}"
                        class="w-full border border-gray-400 px-3 py-2 rounded-none"
                        placeholder="e.g. Timber, fixings, parking"
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2">Quantity</label>

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
                    <label class="block text-sm font-semibold mb-2">Unit amount</label>

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
                >{{ old('contractor_notes') }}</textarea>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Confirmation</h2>

                <div class="border border-gray-300 bg-gray-50 p-4 text-sm mb-4">
                    I confirm this invoice is accurate and is being submitted by me to
                    {{ $settings->company_name ?: $settings->portal_name ?: 'the building firm' }}
                    for payment.
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
                        I confirm and want to submit this invoice.
                    </span>
                </label>
            </section>

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="px-5 py-3 bg-black text-white text-sm font-semibold">
                    Submit invoice
                </button>

                <a href="{{ route('contractor.invoices.index') }}" class="text-sm underline">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        const dayRateInput = document.getElementById('day_rate');
        const warning = document.getElementById('day-rate-warning');
        const weekCommencingInput = document.getElementById('week_commencing');

        function toDateInputValue(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        function getMonday(date) {
            const monday = new Date(date);
            const day = monday.getDay();
            const diff = day === 0 ? -6 : 1 - day;

            monday.setDate(monday.getDate() + diff);

            return monday;
        }

        function formatDisplayDate(date) {
            return new Intl.DateTimeFormat('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            }).format(date);
        }

        function updateDayDates() {
            if (!weekCommencingInput || !weekCommencingInput.value) {
                return;
            }

            const monday = new Date(weekCommencingInput.value + 'T00:00:00');

            document.querySelectorAll('[data-day-offset]').forEach(function (element) {
                const offset = Number(element.dataset.dayOffset);
                const date = new Date(monday);

                date.setDate(monday.getDate() + offset);

                element.textContent = formatDisplayDate(date);
            });
        }

        function snapWeekCommencingToMonday() {
            if (!weekCommencingInput) {
                return;
            }

            if (!weekCommencingInput.value) {
                const today = new Date();
                const monday = getMonday(today);

                weekCommencingInput.value = toDateInputValue(monday);
                updateDayDates();

                return;
            }

            const selectedDate = new Date(weekCommencingInput.value + 'T00:00:00');
            const monday = getMonday(selectedDate);

            weekCommencingInput.value = toDateInputValue(monday);
            updateDayDates();
        }

        if (dayRateInput && warning) {
            const defaultRate = Number(dayRateInput.dataset.defaultRate);

            dayRateInput.addEventListener('input', function () {
                const currentRate = Number(dayRateInput.value);

                if (currentRate !== defaultRate) {
                    warning.classList.remove('hidden');
                } else {
                    warning.classList.add('hidden');
                }
            });
        }

        if (weekCommencingInput) {
            snapWeekCommencingToMonday();

            weekCommencingInput.addEventListener('change', function () {
                snapWeekCommencingToMonday();
            });
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
                    <label class="block text-sm font-semibold mb-2">Type</label>

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
                    <label class="block text-sm font-semibold mb-2">Description</label>

                    <input
                        type="text"
                        name="line_items[${lineItemIndex}][description]"
                        class="w-full border border-gray-400 px-3 py-2 rounded-none"
                        placeholder="e.g. Timber, fixings, parking"
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2">Quantity</label>

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
                    <label class="block text-sm font-semibold mb-2">Unit amount</label>

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