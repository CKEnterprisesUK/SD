<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Quote pricing — {{ $quote->quote_number }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Build and review the quote estimate before sending it to the customer.
                </p>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('admin.pricing-settings.edit') }}" class="inline-flex px-5 py-3 border border-black text-sm font-semibold rounded-none">
                    Rate card settings
                </a>

                <a href="{{ route('admin.quotes.show', $quote) }}" class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
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
                <p class="font-semibold mb-2">There is a problem with the form.</p>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Plain-English assistant explanation -->
        <section class="border border-gray-300 bg-white p-6">
            <div class="max-w-4xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-gray-600">
                    Estimate Assistant
                </p>

                <h1 class="text-3xl font-bold mt-2">
                    Let SiteDesk draft the estimate, then you stay in control.
                </h1>

                <p class="text-gray-700 mt-3">
                    The assistant reads the quote, survey notes, photos, pricing hints, job templates and your rate card.
                    It suggests estimate items and quantities. It does not silently add anything to the quote.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                <div class="border border-gray-300 p-4">
                    <div class="text-sm font-bold">1. Add context</div>
                    <p class="text-sm text-gray-600 mt-1">
                        Tell SiteDesk anything useful about access, measurements, materials or risks.
                    </p>
                </div>

                <div class="border border-gray-300 p-4">
                    <div class="text-sm font-bold">2. AI drafts items</div>
                    <p class="text-sm text-gray-600 mt-1">
                        AI matches the job to your templates and rate card.
                    </p>
                </div>

                <div class="border border-gray-300 p-4">
                    <div class="text-sm font-bold">3. You review</div>
                    <p class="text-sm text-gray-600 mt-1">
                        Accept, reject or edit every suggested item.
                    </p>
                </div>

                <div class="border border-gray-300 p-4">
                    <div class="text-sm font-bold">4. Apply to quote</div>
                    <p class="text-sm text-gray-600 mt-1">
                        Only accepted items are added to the final quote.
                    </p>
                </div>
            </div>
        </section>

        <!-- AI draft generator -->
        <section class="border border-gray-300 bg-white p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <h2 class="text-lg font-semibold">
                        Ask SiteDesk to draft the estimate
                    </h2>

                    <p class="text-sm text-gray-600 mt-2">
                        Write this like a note to an estimator. You do not need perfect detail.
                        The assistant will show missing information clearly before you send the quote.
                    </p>

                    <form method="POST" action="{{ route('admin.quotes.compile-ai', $quote) }}" class="space-y-4 mt-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-semibold mb-2">
                                Extra estimate guidance
                            </label>

                            <textarea
                                id="pricing_hint"
                                name="pricing_hint"
                                rows="6"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                placeholder="Example: Customer is supplying tiles. Allow one skip. Access is awkward. Exclude electrics. Bathroom is around 2.5m x 2m."
                            >{{ old('pricing_hint') }}</textarea>

                            <p class="text-sm text-gray-600 mt-1">
                                Use this for job context, pricing hints and assumptions. SiteDesk will still calculate prices from the rate card.
                            </p>
                        </div>

                        <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                            Draft estimate with AI
                        </button>
                    </form>
                </div>

                <div class="border border-gray-300 bg-gray-50 p-4">
                    <h3 class="text-sm font-bold">
                        What should I tell it?
                    </h3>

                    <ul class="list-disc pl-5 text-sm text-gray-700 mt-3 space-y-2">
                        <li>Measurements or room sizes</li>
                        <li>Who supplies materials</li>
                        <li>Access, parking or waste issues</li>
                        <li>Known exclusions</li>
                        <li>Any customer choices or specification</li>
                        <li>Any gut feeling on labour days or risk</li>
                    </ul>

                    <div class="mt-4 space-y-2">
                        <button type="button" data-hint="Customer is supplying finish materials. Include labour, preparation, waste and preliminaries only." class="js-add-hint block w-full border border-gray-400 bg-white px-3 py-2 text-left text-xs font-semibold">
                            Add: customer supplies materials
                        </button>

                        <button type="button" data-hint="Access is awkward. Allow for additional labour time and waste handling risk." class="js-add-hint block w-full border border-gray-400 bg-white px-3 py-2 text-left text-xs font-semibold">
                            Add: awkward access
                        </button>

                        <button type="button" data-hint="Exclude electrical works unless specifically listed." class="js-add-hint block w-full border border-gray-400 bg-white px-3 py-2 text-left text-xs font-semibold">
                            Add: exclude electrics
                        </button>
                    </div>
                </div>
            </div>
        </section>

        @if ($latestDraft)
            <section class="border border-gray-300 bg-white p-6">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-gray-600">
                            Draft estimate review
                        </p>

                        <h2 class="text-xl font-bold mt-1">
                            Review AI-suggested items
                        </h2>

                        <p class="text-sm text-gray-600 mt-2">
                            Job type:
                            <span class="font-semibold">{{ $latestDraft->detected_job_type ?: 'Not detected' }}</span>
                            · Template:
                            <span class="font-semibold">{{ $latestDraft->selected_template_code ?: 'Not selected' }}</span>
                            · Confidence:
                            <span class="font-semibold">{{ ucfirst($latestDraft->overall_confidence) }}</span>
                        </p>

                        <p class="text-sm text-gray-600 mt-2">
                            Accepting an item only marks it as ready. Use <strong>Apply accepted items</strong> to add it to the real quote total.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <form method="POST" action="{{ route('admin.quotes.ai-drafts.apply-accepted', [$quote, $latestDraft]) }}">
                            @csrf
                            <button
                                type="submit"
                                class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none"
                                onclick="return confirm('Apply accepted AI draft items to this quote?')"
                            >
                                Apply accepted items
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.quotes.ai-drafts.apply-wording', [$quote, $latestDraft]) }}">
                            @csrf
                            <button type="submit" class="px-5 py-3 border border-black text-sm font-semibold rounded-none">
                                Apply customer wording
                            </button>
                        </form>
                    </div>
                </div>

                @if (! empty($latestDraft->missing_information))
                    <div class="mb-6 border border-yellow-700 bg-yellow-50 p-4">
                        <h3 class="font-semibold text-yellow-900">
                            Information to confirm before sending
                        </h3>

                        <p class="text-sm text-yellow-900 mt-1">
                            These are not blockers for drafting, but they should be checked before the customer receives the quote.
                        </p>

                        <ul class="list-disc pl-5 mt-2 text-sm text-yellow-900 space-y-1">
                            @foreach ($latestDraft->missing_information as $missing)
                                <li>
                                    <span class="font-semibold">{{ ucfirst($missing['severity'] ?? 'medium') }}:</span>
                                    {{ $missing['question'] ?? ($missing['field'] ?? 'Information missing') }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (! empty($latestDraft->warnings))
                    <div class="mb-6 border border-red-700 bg-red-50 p-4">
                        <h3 class="font-semibold text-red-900">
                            Pricing warnings
                        </h3>

                        <ul class="list-disc pl-5 mt-2 text-sm text-red-900 space-y-1">
                            @foreach ($latestDraft->warnings as $warning)
                                <li>{{ is_array($warning) ? json_encode($warning) : $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-300 bg-gray-50 text-left">
                                <th class="px-3 py-2">Suggested item</th>
                                <th class="px-3 py-2">Qty</th>
                                <th class="px-3 py-2">Base</th>
                                <th class="px-3 py-2">Markup</th>
                                <th class="px-3 py-2">Subtotal</th>
                                <th class="px-3 py-2">VAT</th>
                                <th class="px-3 py-2">Total</th>
                                <th class="px-3 py-2">Confidence</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($latestDraft->items as $item)
                                <tr class="border-b border-gray-200 align-top" data-draft-item-row data-item-id="{{ $item->id }}">
                                    <td class="px-3 py-3 min-w-80">
                                        <form
                                            id="draft-item-{{ $item->id }}"
                                            method="POST"
                                            action="{{ route('admin.quotes.ai-draft-items.update', [$quote, $item]) }}"
                                            class="space-y-2 js-ai-draft-form"
                                        >
                                            @csrf
                                            @method('PUT')

                                            <select name="pricing_rate_item_id" class="w-full border border-gray-400 px-3 py-2 rounded-none text-xs">
                                                @foreach ($rateItems as $rateItem)
                                                    <option value="{{ $rateItem->id }}" @selected($item->pricing_rate_item_id === $rateItem->id)>
                                                        {{ $rateItem->code }} — {{ $rateItem->name }} (£{{ $rateItem->base_cost }}/{{ $rateItem->unit }})
                                                    </option>
                                                @endforeach
                                            </select>

                                            <input
                                                name="clean_customer_description"
                                                value="{{ $item->clean_customer_description }}"
                                                class="w-full border border-gray-400 px-3 py-2 rounded-none text-xs"
                                                required
                                            >

                                            <textarea
                                                name="warnings"
                                                rows="2"
                                                class="w-full border border-gray-400 px-3 py-2 rounded-none text-xs"
                                                placeholder="Internal warnings"
                                            >{{ implode("\n", $item->warnings ?? []) }}</textarea>
                                        </form>

                                        <p class="text-xs text-gray-500 mt-2 font-mono" data-field="rate_item_code">
                                            {{ $item->rate_item_code }}
                                        </p>

                                        @if ($item->internal_reasoning)
                                            <details class="mt-2">
                                                <summary class="text-xs text-gray-700 underline cursor-pointer">
                                                    Why did AI suggest this?
                                                </summary>
                                                <p class="text-xs text-gray-600 mt-2">
                                                    {{ $item->internal_reasoning }}
                                                </p>
                                            </details>
                                        @endif
                                    </td>

                                    <td class="px-3 py-3">
                                        <input
                                            form="draft-item-{{ $item->id }}"
                                            name="quantity"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            value="{{ $item->quantity }}"
                                            class="w-24 border border-gray-400 px-2 py-1 rounded-none text-xs"
                                            required
                                        >

                                        <div class="text-xs text-gray-500 mt-1" data-field="unit">
                                            {{ $item->unit }}
                                        </div>
                                    </td>

                                    <td class="px-3 py-3">£<span data-field="base_total">{{ $item->base_total }}</span></td>
                                    <td class="px-3 py-3"><span data-field="markup_percent">{{ $item->markup_percent }}</span>%</td>
                                    <td class="px-3 py-3">£<span data-field="subtotal">{{ $item->subtotal }}</span></td>
                                    <td class="px-3 py-3">£<span data-field="vat">{{ $item->vat }}</span></td>
                                    <td class="px-3 py-3 font-semibold">£<span data-field="total">{{ $item->total }}</span></td>

                                    <td class="px-3 py-3">
                                        <select form="draft-item-{{ $item->id }}" name="confidence" class="border border-gray-400 px-2 py-1 rounded-none text-xs">
                                            <option value="low" @selected($item->confidence === 'low')>Low</option>
                                            <option value="medium" @selected($item->confidence === 'medium')>Medium</option>
                                            <option value="high" @selected($item->confidence === 'high')>High</option>
                                        </select>
                                    </td>

                                    <td class="px-3 py-3">
                                        <span
                                            data-item-status
                                            class="inline-flex px-2 py-1 border text-xs font-semibold
                                                @if ($item->status === 'accepted') border-green-700 text-green-800 bg-green-50
                                                @elseif ($item->status === 'rejected') border-red-700 text-red-800 bg-red-50
                                                @elseif ($item->status === 'applied') border-gray-700 text-gray-800 bg-gray-50
                                                @else border-yellow-700 text-yellow-800 bg-yellow-50
                                                @endif"
                                        >
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-3 space-y-2 min-w-36">
                                        <button
                                            form="draft-item-{{ $item->id }}"
                                            type="submit"
                                            class="block w-full px-3 py-2 bg-black text-white text-xs font-semibold rounded-none"
                                        >
                                            Save + accept
                                        </button>

                                        <form method="POST" action="{{ route('admin.quotes.ai-draft-items.accept', [$quote, $item]) }}" class="js-ai-draft-form">
                                            @csrf
                                            <button type="submit" class="block w-full px-3 py-2 border border-black text-xs font-semibold rounded-none">
                                                Accept
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.quotes.ai-draft-items.reject', [$quote, $item]) }}" class="js-ai-draft-form">
                                            @csrf
                                            <button type="submit" class="block w-full px-3 py-2 border border-red-700 text-red-700 text-xs font-semibold rounded-none">
                                                Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-3 py-8 text-center text-gray-600">
                                        This AI draft does not contain priced items. Check rate-card coverage and pricing warnings.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

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

                                            <button type="submit" class="text-xs underline text-red-700" onclick="return confirm('Delete this line item?')">
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
                    Add manual line item
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
            const hintBox = document.getElementById('pricing_hint');

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

            function updateStatusBadge(row, status, label) {
                const badge = row.querySelector('[data-item-status]');

                if (!badge) {
                    return;
                }

                badge.textContent = label || status;
                badge.className = 'inline-flex px-2 py-1 border text-xs font-semibold';

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

            function updateRowPrices(row, item) {
                const fields = [
                    'rate_item_code',
                    'base_total',
                    'markup_percent',
                    'subtotal',
                    'vat',
                    'total',
                    'unit',
                ];

                fields.forEach(function (field) {
                    const el = row.querySelector('[data-field="' + field + '"]');

                    if (el && item[field] !== undefined && item[field] !== null) {
                        el.textContent = item[field];
                    }
                });
            }

            document.querySelectorAll('.js-add-hint').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!hintBox) {
                        return;
                    }

                    const current = hintBox.value.trim();
                    const extra = button.dataset.hint || '';

                    hintBox.value = current ? current + "\n" + extra : extra;
                    hintBox.focus();
                });
            });

            document.querySelectorAll('.js-ai-draft-form').forEach(function (form) {
                form.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    const row = form.closest('[data-draft-item-row]');
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
                                const messages = Object.values(data.errors).flat().join(' ');
                                throw new Error(messages);
                            }

                            throw new Error(data.message || 'The action could not be completed.');
                        }

                        if (row && data.item) {
                            updateStatusBadge(row, data.item.status, data.item.status_label);
                            updateRowPrices(row, data.item);
                        }

                        showStatus(data.message || 'Saved.');
                    } catch (error) {
                        showStatus(error.message || 'There was a problem saving this item.', 'error');
                    } finally {
                        if (button) {
                            button.disabled = false;
                            button.textContent = originalText;
                        }
                    }
                });
            });
        });
    </script>
</x-app-layout>