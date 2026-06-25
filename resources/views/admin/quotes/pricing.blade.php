<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Pricing — {{ $quote->quote_number }}
                </h2>
            </div>

            <div class="flex flex-wrap gap-3">
                @if (Route::has('admin.pricing-settings.edit'))
                    <a href="{{ route('admin.pricing-settings.edit') }}"
                       class="inline-flex px-5 py-3 border border-black text-sm font-semibold rounded-none">
                        Pricing settings
                    </a>
                @endif

                @if (Route::has('admin.quotes.pack'))
                    <a href="{{ route('admin.quotes.pack', $quote) }}"
                       class="inline-flex px-5 py-3 border border-black text-sm font-semibold rounded-none">
                        Customer pack
                    </a>
                @endif

                <a href="{{ route('admin.quotes.show', $quote) }}"
                   class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Back to quote
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $generateEstimateRoute = Route::has('admin.quotes.generate-ai-estimate')
            ? route('admin.quotes.generate-ai-estimate', $quote)
            : route('admin.quotes.compile-ai', $quote);

        $lineItemUpdateRouteExists = Route::has('admin.quotes.line-items.update');
    @endphp

    <div class="max-w-7xl mx-auto py-8 px-4 space-y-8">
        @if (session('status'))
            <div class="border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="border border-red-700 bg-red-50 px-4 py-3 text-sm text-red-900">
                <p class="font-semibold mb-2">There is a problem.</p>

                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="border border-gray-300 bg-white p-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                <div>
                    <h1 class="text-2xl font-bold">
                        Quote pricing
                    </h1>

                    <p class="text-sm text-gray-600 mt-2">
                        Edit the final quote line items below. These are the items used in the quote total.
                    </p>
                </div>

                <form method="POST" action="{{ $generateEstimateRoute }}" class="lg:w-[520px] space-y-3">
                    @csrf

                    <textarea
                        name="pricing_hint"
                        rows="3"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                        placeholder="Optional pricing note..."
                    >{{ old('pricing_hint') }}</textarea>

                    <button type="submit" class="w-full px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                        Generate AI estimate
                    </button>
                </form>
            </div>
        </section>

        @if ($latestDraft)
            <section class="border border-gray-300 bg-white p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">
                            Latest AI estimate
                        </h2>

                        <p class="text-sm text-gray-600 mt-1">
                            {{ $latestDraft->detected_job_type ?: 'Job type not detected' }}
                            · Confidence: {{ ucfirst($latestDraft->overall_confidence) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('admin.quotes.ai-drafts.apply-accepted', [$quote, $latestDraft]) }}">
                            @csrf

                            <button
                                type="submit"
                                class="px-5 py-3 border border-black text-sm font-semibold rounded-none"
                                onclick="return confirm('Apply accepted AI items to this quote?')"
                            >
                                Apply accepted AI items
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <section class="xl:col-span-2 border border-gray-300 bg-white">
                <div class="p-6 border-b border-gray-300">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold">
                                Line items
                            </h2>

                            <p class="text-sm text-gray-600 mt-1">
                                Edit the items that will appear in the customer quote.
                            </p>
                        </div>

                        <div class="border border-gray-300 bg-gray-50 px-4 py-3 text-sm">
                            <div class="font-semibold">Quote total</div>
                            <div class="text-xl font-bold">£{{ $quote->total }}</div>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    @forelse ($quote->lineItems as $lineItem)
                        <article class="border border-gray-300 bg-white">
                            <form
                                method="POST"
                                action="{{ $lineItemUpdateRouteExists ? route('admin.quotes.line-items.update', [$quote, $lineItem]) : '#' }}"
                                class="p-4 space-y-4"
                            >
                                @csrf

                                @if ($lineItemUpdateRouteExists)
                                    @method('PUT')
                                @endif

                                <div class="grid grid-cols-1 lg:grid-cols-[1fr_180px] gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">
                                            Description
                                        </label>

                                        <textarea
                                            name="description"
                                            rows="2"
                                            class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm font-semibold"
                                            required
                                            @disabled(! $lineItemUpdateRouteExists)
                                        >{{ old('line_items.' . $lineItem->id . '.description', $lineItem->description) }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold mb-1">
                                            Subtotal
                                        </label>

                                        <div class="border border-gray-300 bg-gray-50 px-3 py-2 text-lg font-bold">
                                            £{{ $lineItem->total }}
                                        </div>

                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $lineItem->source }}
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">
                                            Type
                                        </label>

                                        <input
                                            name="type"
                                            type="text"
                                            value="{{ old('line_items.' . $lineItem->id . '.type', $lineItem->type) }}"
                                            class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                            required
                                            @disabled(! $lineItemUpdateRouteExists)
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold mb-1">
                                            Qty
                                        </label>

                                        <input
                                            name="quantity"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value="{{ old('line_items.' . $lineItem->id . '.quantity', $lineItem->quantity) }}"
                                            class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                            required
                                            @disabled(! $lineItemUpdateRouteExists)
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold mb-1">
                                            Unit
                                        </label>

                                        <input
                                            name="unit"
                                            type="text"
                                            value="{{ old('line_items.' . $lineItem->id . '.unit', $lineItem->unit) }}"
                                            class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                            required
                                            @disabled(! $lineItemUpdateRouteExists)
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold mb-1">
                                            Unit amount
                                        </label>

                                        <input
                                            name="unit_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value="{{ old('line_items.' . $lineItem->id . '.unit_amount', $lineItem->unit_amount) }}"
                                            class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                            required
                                            @disabled(! $lineItemUpdateRouteExists)
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold mb-1">
                                            Optional
                                        </label>

                                        <label class="flex items-center gap-2 border border-gray-400 px-3 py-2 text-sm h-[38px]">
                                            <input
                                                type="checkbox"
                                                name="is_optional"
                                                value="1"
                                                @checked(old('line_items.' . $lineItem->id . '.is_optional', $lineItem->is_optional))
                                                @disabled(! $lineItemUpdateRouteExists)
                                            >

                                            Yes
                                        </label>
                                    </div>

                                    <div class="flex items-end">
                                        @if ($lineItemUpdateRouteExists)
                                            <button
                                                type="submit"
                                                class="w-full px-4 py-2 bg-black text-white text-sm font-semibold rounded-none"
                                            >
                                                Save
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                class="w-full px-4 py-2 border border-gray-400 text-gray-500 text-sm font-semibold rounded-none cursor-not-allowed"
                                                disabled
                                            >
                                                Save
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </form>

                            <div class="border-t border-gray-200 px-4 py-3 flex justify-between items-center">
                                <div class="text-xs text-gray-500">
                                    {{ ucfirst(str_replace('_', ' ', $lineItem->type)) }}
                                    @if ($lineItem->is_optional)
                                        · Optional
                                    @endif
                                </div>

                                <form method="POST" action="{{ route('admin.quotes.line-items.destroy', [$quote, $lineItem]) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="text-xs underline text-red-700"
                                        onclick="return confirm('Delete this line item?')"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <div class="border border-gray-300 bg-gray-50 p-8 text-center text-gray-600">
                            No line items have been added yet.
                        </div>
                    @endforelse
                </div>

                <div class="border-t border-gray-300 bg-gray-50 p-6">
                    <div class="max-w-md ml-auto space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span class="font-semibold">£{{ $quote->subtotal }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span>VAT</span>
                            <span class="font-semibold">£{{ $quote->vat }}</span>
                        </div>

                        <div class="flex justify-between border-t border-gray-300 pt-3 text-lg">
                            <span class="font-bold">Total</span>
                            <span class="font-bold">£{{ $quote->total }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="space-y-6">
                <section class="border border-gray-300 bg-white p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        Add line item
                    </h2>

                    <form method="POST" action="{{ route('admin.quotes.line-items.store', $quote) }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-semibold mb-2">Type</label>

                            <input
                                name="type"
                                type="text"
                                value="{{ old('type', 'works') }}"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                required
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">Description</label>

                            <textarea
                                name="description"
                                rows="3"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                required
                            >{{ old('description') }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold mb-2">Qty</label>

                                <input
                                    name="quantity"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    value="{{ old('quantity', '1') }}"
                                    class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                    required
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Unit</label>

                                <input
                                    name="unit"
                                    type="text"
                                    value="{{ old('unit', 'item') }}"
                                    class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                    required
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">Unit amount before VAT</label>

                            <input
                                name="unit_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                value="{{ old('unit_amount', '0.00') }}"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                required
                            >
                        </div>

                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_optional" value="1" @checked(old('is_optional'))>
                            Optional item
                        </label>

                        <button type="submit" class="w-full px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                            Add line item
                        </button>
                    </form>
                </section>

                <section class="border border-gray-300 bg-white p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        Totals
                    </h2>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span class="font-semibold">£{{ $quote->subtotal }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span>VAT</span>
                            <span class="font-semibold">£{{ $quote->vat }}</span>
                        </div>

                        <div class="flex justify-between border-t border-gray-300 pt-3 text-lg">
                            <span class="font-bold">Total</span>
                            <span class="font-bold">£{{ $quote->total }}</span>
                        </div>
                    </div>
                </section>

                @unless ($lineItemUpdateRouteExists)
                    <section class="border border-yellow-700 bg-yellow-50 p-6 text-sm text-yellow-900">
                        <p class="font-semibold">
                            Line item editing route missing
                        </p>

                        <p class="mt-2">
                            The page is ready for editing, but the update route still needs adding:
                        </p>

                        <pre class="mt-3 whitespace-pre-wrap text-xs">admin.quotes.line-items.update</pre>
                    </section>
                @endunless
            </aside>
        </div>
    </div>
</x-app-layout>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const statusBox = document.getElementById('ajax-status');
        const acceptAllButton = document.getElementById('accept-all-items');

        function showStatus(message, type = 'success') {
            if (!statusBox) {
                return;
            }

            statusBox.textContent = message;
            statusBox.className = 'border px-4 py-3 text-sm';

            if (type === 'error') {
                statusBox.classList.add('border-red-700', 'bg-red-50', 'text-red-900');
            } else {
                statusBox.classList.add('border-green-700', 'bg-green-50', 'text-green-900');
            }

            statusBox.classList.remove('hidden');
        }

        function setRowState(row, status) {
            row.dataset.status = status;

            row.classList.remove(
                'border-gray-300',
                'border-green-700',
                'border-red-700',
                'border-gray-700',
                'bg-white',
                'bg-green-50',
                'bg-red-50',
                'bg-gray-50'
            );

            if (status === 'accepted') {
                row.classList.add('border-green-700', 'bg-green-50');
            } else if (status === 'rejected') {
                row.classList.add('border-red-700', 'bg-red-50');
            } else if (status === 'applied') {
                row.classList.add('border-gray-700', 'bg-gray-50');
            } else {
                row.classList.add('border-gray-300', 'bg-white');
            }

            const badge = row.querySelector('[data-status-badge]');

            if (badge) {
                badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                badge.className = 'inline-flex px-3 py-1 border text-xs font-semibold';

                if (status === 'accepted') {
                    badge.classList.add('border-green-700', 'text-green-800', 'bg-green-50');
                } else if (status === 'rejected') {
                    badge.classList.add('border-red-700', 'text-red-800', 'bg-red-50');
                } else if (status === 'applied') {
                    badge.classList.add('border-gray-700', 'text-gray-800', 'bg-gray-50');
                } else {
                    badge.classList.add('border-yellow-700', 'text-yellow-800', 'bg-yellow-50');
                }
            }

            if (['accepted', 'rejected', 'applied'].includes(status)) {
                row.classList.add('hidden');
            } else {
                row.classList.remove('hidden');
            }

            updateCounts();
            updateEmptyState();
        }

        function updateRowPrices(row, item) {
            if (item.likely_total !== undefined && item.likely_total !== null) {
                row.dataset.likely = item.likely_total.replace(/,/g, '');
            }

            const totalEl = row.querySelector('[data-field="total"]');

            if (totalEl && item.total !== undefined && item.total !== null) {
                totalEl.textContent = item.total;
            }

            updateCounts();
        }

        function updateCounts() {
            const rows = Array.from(document.querySelectorAll('[data-item-row]'));

            const acceptedRows = rows.filter(row => row.dataset.status === 'accepted');
            const pendingRows = rows.filter(row => row.dataset.status === 'pending');

            const acceptedEl = document.getElementById('accepted-count');
            const pendingEl = document.getElementById('pending-count');
            const acceptedTotalEl = document.getElementById('accepted-total');

            if (acceptedEl) {
                acceptedEl.textContent = acceptedRows.length;
            }

            if (pendingEl) {
                pendingEl.textContent = pendingRows.length;
            }

            if (acceptedTotalEl) {
                const total = acceptedRows.reduce(function (sum, row) {
                    return sum + (parseFloat(row.dataset.likely || '0') || 0);
                }, 0);

                acceptedTotalEl.textContent = total.toLocaleString('en-GB', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        }

        function updateEmptyState() {
            const visiblePendingRows = Array.from(document.querySelectorAll('[data-item-row]'))
                .filter(row => row.dataset.status === 'pending' && !row.classList.contains('hidden'));

            let emptyState = document.getElementById('estimate-empty-state');

            if (visiblePendingRows.length === 0) {
                if (!emptyState) {
                    const container = document.querySelector('[data-estimate-items-container]');

                    if (container) {
                        emptyState = document.createElement('div');
                        emptyState.id = 'estimate-empty-state';
                        emptyState.className = 'border border-green-700 bg-green-50 p-6 text-sm text-green-900';
                        emptyState.textContent = 'All estimate items have been reviewed. Apply the accepted items to the quote when ready.';

                        container.appendChild(emptyState);
                    }
                }
            } else if (emptyState) {
                emptyState.remove();
            }
        }

        async function submitAjaxForm(form) {
            let row = form.closest('[data-item-row]');

            if (!row && form.id && form.id.startsWith('draft-item-')) {
                const id = form.id.replace('draft-item-', '');
                row = document.querySelector('[data-item-row][data-item-id="' + id + '"]');
            }

            const button = form.querySelector('button[type="submit"]');
            const originalText = button ? button.textContent : null;

            if (button) {
                button.disabled = true;
                button.textContent = 'Saving...';
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    if (data.errors) {
                        throw new Error(Object.values(data.errors).flat().join(' '));
                    }

                    throw new Error(data.message || 'The item could not be saved.');
                }

                if (row && data.item) {
                    updateRowPrices(row, data.item);
                    setRowState(row, data.item.status);
                }

                return data;
            } finally {
                if (button) {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            }
        }

        document.querySelectorAll('.js-ai-item-form').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                try {
                    const data = await submitAjaxForm(form);
                    showStatus(data.message || 'Saved.');
                } catch (error) {
                    showStatus(error.message || 'There was a problem saving this item.', 'error');
                }
            });
        });

        if (acceptAllButton) {
            acceptAllButton.addEventListener('click', async function () {
                const forms = Array.from(document.querySelectorAll('.js-accept-form'))
                    .filter(function (form) {
                        const row = form.closest('[data-item-row]');

                        return row &&
                            row.dataset.status === 'pending' &&
                            !row.classList.contains('hidden');
                    });

                if (forms.length === 0) {
                    showStatus('There are no pending items to accept.');
                    return;
                }

                acceptAllButton.disabled = true;
                acceptAllButton.textContent = 'Accepting...';

                let accepted = 0;
                let failed = 0;

                for (const form of forms) {
                    try {
                        await submitAjaxForm(form);
                        accepted++;
                    } catch (error) {
                        failed++;
                    }
                }

                acceptAllButton.disabled = false;
                acceptAllButton.textContent = 'Accept all';

                if (failed > 0) {
                    showStatus(accepted + ' items accepted. ' + failed + ' items could not be accepted.', 'error');
                } else {
                    showStatus(accepted + ' items accepted.');
                }

                updateEmptyState();
            });
        }

        updateCounts();
        updateEmptyState();
    });
</script>
</x-app-layout>