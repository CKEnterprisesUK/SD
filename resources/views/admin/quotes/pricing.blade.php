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

                <a href="{{ route('admin.quotes.show', $quote) }}"
                   class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Back to quote
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4 space-y-8">
        <div id="ajax-status" class="hidden border px-4 py-3 text-sm"></div>

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

        <!-- Generate estimate -->
        <section class="border border-gray-300 bg-white p-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                <div>
                    <h1 class="text-2xl font-bold">
                        AI estimate
                    </h1>

                    <p class="text-sm text-gray-600 mt-2 max-w-3xl">
                        Generate a draft, accept the items you want, then apply them to the quote.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.quotes.compile-ai', $quote) }}" class="lg:w-[520px] space-y-3">
                    @csrf

                    <textarea
                        name="pricing_hint"
                        rows="3"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                        placeholder="Optional note: access is difficult, exclude electrics, customer supplies materials..."
                    >{{ old('pricing_hint') }}</textarea>

                    <button type="submit" class="w-full px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                        Generate AI estimate
                    </button>
                </form>
            </div>
        </section>

        @if ($latestDraft)
            <!-- Draft review -->
            <section class="border border-gray-300 bg-white">
                <div class="p-6 border-b border-gray-300">
                    <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold">
                                Review estimate
                            </h2>

                            <div class="flex flex-wrap gap-2 mt-3 text-xs">
                                <span class="inline-flex border border-gray-300 px-2 py-1">
                                    {{ $latestDraft->detected_job_type ?: 'Job type not detected' }}
                                </span>

                                <span class="inline-flex border border-gray-300 px-2 py-1">
                                    Confidence: {{ ucfirst($latestDraft->overall_confidence) }}
                                </span>

                                <span class="inline-flex border border-gray-300 px-2 py-1">
                                    <span id="accepted-count">0</span> accepted
                                </span>

                                <span class="inline-flex border border-gray-300 px-2 py-1">
                                    <span id="pending-count">0</span> pending
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button
                                type="button"
                                id="accept-all-items"
                                class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none"
                            >
                                Accept all
                            </button>

                            <form method="POST" action="{{ route('admin.quotes.ai-drafts.apply-accepted', [$quote, $latestDraft]) }}">
                                @csrf

                                <button
                                    type="submit"
                                    class="px-5 py-3 border border-black text-sm font-semibold rounded-none"
                                    onclick="return confirm('Apply accepted items to this quote?')"
                                >
                                    Apply accepted to quote
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.quotes.ai-drafts.apply-wording', [$quote, $latestDraft]) }}">
                                @csrf

                                <button type="submit" class="px-5 py-3 border border-black text-sm font-semibold rounded-none">
                                    Apply wording
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                @if (! empty($latestDraft->missing_information) || ! empty($latestDraft->warnings))
                    <div class="p-6 border-b border-gray-300 bg-gray-50">
                        @if (! empty($latestDraft->missing_information))
                            <div class="mb-4">
                                <h3 class="font-semibold text-sm">
                                    Missing information
                                </h3>

                                <ul class="list-disc pl-5 mt-2 text-sm text-gray-700 space-y-1">
                                    @foreach ($latestDraft->missing_information as $missing)
                                        <li>
                                            {{ $missing['question'] ?? ($missing['field'] ?? 'Information missing') }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (! empty($latestDraft->warnings))
                            <div>
                                <h3 class="font-semibold text-sm">
                                    Warnings
                                </h3>

                                <ul class="list-disc pl-5 mt-2 text-sm text-gray-700 space-y-1">
                                    @foreach ($latestDraft->warnings as $warning)
                                        <li>{{ is_array($warning) ? json_encode($warning) : $warning }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="p-6 space-y-4">
                    @forelse ($latestDraft->items as $item)
                        @php
                            $status = $item->status ?? 'pending';
                            $warningsText = is_array($item->warnings ?? null)
                                ? implode("\n", $item->warnings ?? [])
                                : ($item->warnings ?? '');

                            $lowEstimate = number_format(($item->low_total_pence ?? 0) / 100, 2, '.', '');
                            $likelyEstimate = number_format(($item->likely_total_pence ?? $item->subtotal_pence ?? 0) / 100, 2, '.', '');
                            $highEstimate = number_format(($item->high_total_pence ?? 0) / 100, 2, '.', '');
                        @endphp

                        <article
                            data-item-card
                            data-status="{{ $status }}"
                            data-item-id="{{ $item->id }}"
                            class="border p-4 transition
                                @if ($status === 'accepted') border-green-700 bg-green-50
                                @elseif ($status === 'rejected') border-red-700 bg-red-50
                                @elseif ($status === 'applied') border-gray-700 bg-gray-50
                                @else border-gray-300 bg-white
                                @endif"
                        >
                            <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                                <div class="xl:col-span-5">
                                    <form
                                        id="draft-item-{{ $item->id }}"
                                        method="POST"
                                        action="{{ route('admin.quotes.ai-draft-items.update', [$quote, $item]) }}"
                                        class="space-y-3 js-ai-item-form"
                                    >
                                        @csrf
                                        @method('PUT')

                                        <input
                                            name="clean_customer_description"
                                            value="{{ $item->clean_customer_description }}"
                                            class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm font-semibold"
                                            required
                                        >

                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                            <div>
                                                <label class="block text-xs font-semibold mb-1">
                                                    Qty
                                                </label>

                                                <input
                                                    name="quantity"
                                                    type="number"
                                                    step="0.01"
                                                    min="0.01"
                                                    value="{{ $item->quantity }}"
                                                    class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                                    required
                                                >
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold mb-1">
                                                    Unit
                                                </label>

                                                <input
                                                    name="unit"
                                                    value="{{ $item->unit }}"
                                                    class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                                    required
                                                >
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold mb-1">
                                                    Confidence
                                                </label>

                                                <select name="confidence" class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm">
                                                    <option value="low" @selected($item->confidence === 'low')>Low</option>
                                                    <option value="medium" @selected($item->confidence === 'medium')>Medium</option>
                                                    <option value="high" @selected($item->confidence === 'high')>High</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold mb-1">
                                                    Basis
                                                </label>

                                                <select name="pricing_basis" class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm">
                                                    @foreach ([
                                                        'pricing_guidance' => 'Guidance',
                                                        'project_template' => 'Template',
                                                        'historical_guidance' => 'History',
                                                        'user_hint' => 'User hint',
                                                        'provisional_allowance' => 'Allowance',
                                                        'professional_estimate' => 'Estimate',
                                                        'market_assumption' => 'Market',
                                                        'ai_estimate' => 'AI estimate',
                                                    ] as $value => $label)
                                                        <option value="{{ $value }}" @selected(($item->pricing_basis ?? $item->pricing_source) === $value)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs font-semibold mb-1">
                                                    Low ex VAT
                                                </label>

                                                <input
                                                    name="low_estimate_ex_vat"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value="{{ $lowEstimate }}"
                                                    class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                                    required
                                                >
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold mb-1">
                                                    Likely ex VAT
                                                </label>

                                                <input
                                                    name="likely_estimate_ex_vat"
                                                    type="number"
                                                    step="0.01"
                                                    min="0.01"
                                                    value="{{ $likelyEstimate }}"
                                                    class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                                    required
                                                >
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold mb-1">
                                                    High ex VAT
                                                </label>

                                                <input
                                                    name="high_estimate_ex_vat"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value="{{ $highEstimate }}"
                                                    class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                                    required
                                                >
                                            </div>
                                        </div>

                                        <textarea
                                            name="estimate_explanation"
                                            rows="2"
                                            class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                            placeholder="Short internal note"
                                        >{{ $item->estimate_explanation }}</textarea>

                                        <textarea
                                            name="warnings"
                                            rows="2"
                                            class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                            placeholder="Warnings"
                                        >{{ $warningsText }}</textarea>
                                    </form>
                                </div>

                                <div class="xl:col-span-4">
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div class="border border-gray-300 p-3 bg-white">
                                            <div class="text-xs text-gray-500">Low</div>
                                            <div class="font-bold">£<span data-field="low_total">{{ $item->low_total ?? $lowEstimate }}</span></div>
                                        </div>

                                        <div class="border border-gray-300 p-3 bg-white">
                                            <div class="text-xs text-gray-500">Likely ex VAT</div>
                                            <div class="font-bold">£<span data-field="likely_total">{{ $item->likely_total ?? $likelyEstimate }}</span></div>
                                        </div>

                                        <div class="border border-gray-300 p-3 bg-white">
                                            <div class="text-xs text-gray-500">High</div>
                                            <div class="font-bold">£<span data-field="high_total">{{ $item->high_total ?? $highEstimate }}</span></div>
                                        </div>

                                        <div class="border border-gray-300 p-3 bg-white">
                                            <div class="text-xs text-gray-500">Inc VAT</div>
                                            <div class="font-bold">£<span data-field="total">{{ $item->total }}</span></div>
                                        </div>
                                    </div>

                                    @if ($item->internal_reasoning)
                                        <details class="mt-3 text-sm">
                                            <summary class="cursor-pointer underline">
                                                Reasoning
                                            </summary>

                                            <p class="mt-2 text-gray-700">
                                                {{ $item->internal_reasoning }}
                                            </p>
                                        </details>
                                    @endif
                                </div>

                                <div class="xl:col-span-3 flex flex-col justify-between gap-4">
                                    <div>
                                        <span
                                            data-status-badge
                                            class="inline-flex px-3 py-1 border text-xs font-semibold
                                                @if ($status === 'accepted') border-green-700 text-green-800 bg-green-50
                                                @elseif ($status === 'rejected') border-red-700 text-red-800 bg-red-50
                                                @elseif ($status === 'applied') border-gray-700 text-gray-800 bg-gray-50
                                                @else border-yellow-700 text-yellow-800 bg-yellow-50
                                                @endif"
                                        >
                                            {{ ucfirst($status) }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 gap-2">
                                        <button
                                            form="draft-item-{{ $item->id }}"
                                            type="submit"
                                            class="w-full px-4 py-2 bg-black text-white text-sm font-semibold rounded-none"
                                        >
                                            Save + accept
                                        </button>

                                        <form method="POST"
                                              action="{{ route('admin.quotes.ai-draft-items.accept', [$quote, $item]) }}"
                                              class="js-ai-item-form js-accept-form">
                                            @csrf

                                            <button type="submit" class="w-full px-4 py-2 border border-black text-sm font-semibold rounded-none">
                                                Accept
                                            </button>
                                        </form>

                                        <form method="POST"
                                              action="{{ route('admin.quotes.ai-draft-items.reject', [$quote, $item]) }}"
                                              class="js-ai-item-form">
                                            @csrf

                                            <button type="submit" class="w-full px-4 py-2 border border-red-700 text-red-700 text-sm font-semibold rounded-none">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="border border-gray-300 bg-gray-50 p-8 text-center text-gray-600">
                            No AI estimate items yet.
                        </div>
                    @endforelse
                </div>
            </section>
        @endif

        <!-- Current quote items and manual add -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">
                    Quote line items
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-300 bg-gray-50 text-left">
                                <th class="px-3 py-2 font-semibold">Description</th>
                                <th class="px-3 py-2 font-semibold">Qty</th>
                                <th class="px-3 py-2 font-semibold">Unit</th>
                                <th class="px-3 py-2 font-semibold">Subtotal</th>
                                <th class="px-3 py-2 font-semibold">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($quote->lineItems as $lineItem)
                                <tr class="border-b border-gray-200">
                                    <td class="px-3 py-3">
                                        <div class="font-semibold">{{ $lineItem->description }}</div>

                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ ucfirst(str_replace('_', ' ', $lineItem->type)) }}
                                            · {{ $lineItem->source }}

                                            @if ($lineItem->is_optional)
                                                · Optional
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-3 py-3">{{ $lineItem->quantity }}</td>
                                    <td class="px-3 py-3">£{{ $lineItem->unit_amount }} / {{ $lineItem->unit }}</td>
                                    <td class="px-3 py-3 font-semibold">£{{ $lineItem->total }}</td>

                                    <td class="px-3 py-3">
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
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-8 text-center text-gray-600">
                                        No line items have been added yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="3" class="px-3 py-3 text-right">Subtotal</th>
                                <th class="px-3 py-3 text-left">£{{ $quote->subtotal }}</th>
                                <th></th>
                            </tr>

                            <tr>
                                <th colspan="3" class="px-3 py-3 text-right">VAT</th>
                                <th class="px-3 py-3 text-left">£{{ $quote->vat }}</th>
                                <th></th>
                            </tr>

                            <tr>
                                <th colspan="3" class="px-3 py-3 text-right">Total</th>
                                <th class="px-3 py-3 text-left">£{{ $quote->total }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">
                    Add manual item
                </h2>

                <form method="POST" action="{{ route('admin.quotes.line-items.store', $quote) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold mb-2">Type</label>
                        <input name="type" type="text" value="works" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Description</label>
                        <input name="description" type="text" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Quantity</label>
                        <input name="quantity" type="number" min="0.01" step="0.01" value="1" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Unit</label>
                        <input name="unit" type="text" value="item" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Unit amount before VAT</label>
                        <input name="unit_amount" type="number" min="0" step="0.01" value="0.00" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_optional" value="1">
                        Optional item
                    </label>

                    <button type="submit" class="w-full px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                        Add line item
                    </button>
                </form>
            </section>
        </div>
    </div>

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

            function setCardState(card, status) {
                card.dataset.status = status;

                card.classList.remove(
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
                    card.classList.add('border-green-700', 'bg-green-50');
                } else if (status === 'rejected') {
                    card.classList.add('border-red-700', 'bg-red-50');
                } else if (status === 'applied') {
                    card.classList.add('border-gray-700', 'bg-gray-50');
                } else {
                    card.classList.add('border-gray-300', 'bg-white');
                }

                const badge = card.querySelector('[data-status-badge]');

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

                updateCounts();
            }

            function updateCardPrices(card, item) {
                const map = {
                    low_total: item.low_total,
                    likely_total: item.likely_total,
                    high_total: item.high_total,
                    total: item.total
                };

                Object.keys(map).forEach(function (key) {
                    const el = card.querySelector('[data-field="' + key + '"]');

                    if (el && map[key] !== undefined && map[key] !== null) {
                        el.textContent = map[key];
                    }
                });
            }

            function updateCounts() {
                const cards = Array.from(document.querySelectorAll('[data-item-card]'));

                const accepted = cards.filter(card => card.dataset.status === 'accepted').length;
                const pending = cards.filter(card => card.dataset.status === 'pending').length;

                const acceptedEl = document.getElementById('accepted-count');
                const pendingEl = document.getElementById('pending-count');

                if (acceptedEl) {
                    acceptedEl.textContent = accepted;
                }

                if (pendingEl) {
                    pendingEl.textContent = pending;
                }
            }

            async function submitAjaxForm(form) {
                const card = form.closest('[data-item-card]');
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

                    if (card && data.item) {
                        setCardState(card, data.item.status);
                        updateCardPrices(card, data.item);
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
                            const card = form.closest('[data-item-card]');
                            return card && card.dataset.status !== 'accepted' && card.dataset.status !== 'applied';
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
                });
            }

            updateCounts();
        });
    </script>
</x-app-layout>