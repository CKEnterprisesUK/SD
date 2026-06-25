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

        $lineItems = $quote->lineItems ?? collect();

        $latestDraftItemsToApply = collect();

        if ($latestDraft && $latestDraft->relationLoaded('items')) {
            $latestDraftItemsToApply = $latestDraft->items
                ->filter(fn ($item) => ! in_array($item->status, ['rejected', 'applied'], true))
                ->values();
        }
    @endphp

    <div class="max-w-7xl mx-auto py-8 px-4 space-y-6">
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

        <section class="border border-gray-300 bg-white">
            <div class="p-6 border-b border-gray-300">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div>
                        <h1 class="text-2xl font-bold">
                            Quote pricing
                        </h1>

                        <p class="text-sm text-gray-600 mt-2 max-w-2xl">
                            Add and edit the priced rows that will appear in the customer quote.
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-sm w-full lg:w-auto lg:min-w-[420px]">
                        <div class="border border-gray-300 bg-gray-50 p-3">
                            <div class="text-xs text-gray-500">Subtotal</div>
                            <div class="font-bold text-lg">£{{ $quote->subtotal }}</div>
                        </div>

                        <div class="border border-gray-300 bg-gray-50 p-3">
                            <div class="text-xs text-gray-500">VAT</div>
                            <div class="font-bold text-lg">£{{ $quote->vat }}</div>
                        </div>

                        <div class="border border-black bg-white p-3">
                            <div class="text-xs text-gray-500">Total</div>
                            <div class="font-bold text-lg">£{{ $quote->total }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 border-b border-gray-300 bg-gray-50">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold">
                            AI estimate
                        </h2>

                        @if ($latestDraft)
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $latestDraft->detected_job_type ?: 'Job type not detected' }}
                                · {{ $latestDraftItemsToApply->count() }} item{{ $latestDraftItemsToApply->count() === 1 ? '' : 's' }} ready to add
                            </p>
                        @else
                            <p class="text-sm text-gray-600 mt-1">
                                Generate a draft estimate, then add it into the editable line items.
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        @if ($latestDraft && $latestDraftItemsToApply->isNotEmpty())
                            <form method="POST" action="{{ route('admin.quotes.ai-drafts.apply-accepted', [$quote, $latestDraft]) }}">
                                @csrf

                                <button
                                    type="submit"
                                    class="w-full sm:w-auto px-5 py-3 bg-black text-white text-sm font-semibold rounded-none"
                                    onclick="return confirm('Add the AI estimate items to this quote? You can edit them afterwards.')"
                                >
                                    Add AI estimate to line items
                                </button>
                            </form>
                        @endif

                        <details class="border border-gray-300 bg-white">
                            <summary class="cursor-pointer px-5 py-3 text-sm font-semibold">
                                Generate new estimate
                            </summary>

                            <form method="POST" action="{{ $generateEstimateRoute }}" class="p-4 border-t border-gray-300 space-y-3 w-full sm:w-[420px]">
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
                        </details>
                    </div>
                </div>
            </div>

            <div class="hidden">
                <form id="add-line-item-form" method="POST" action="{{ route('admin.quotes.line-items.store', $quote) }}">
                    @csrf
                </form>

                @foreach ($lineItems as $lineItem)
                    <form id="line-item-form-{{ $lineItem->id }}" method="POST" action="{{ route('admin.quotes.line-items.update', [$quote, $lineItem]) }}">
                        @csrf
                        @method('PUT')
                    </form>
                @endforeach
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm table-fixed">
                    <colgroup>
                        <col style="width: 34%;">
                        <col style="width: 12%;">
                        <col style="width: 8%;">
                        <col style="width: 8%;">
                        <col style="width: 11%;">
                        <col style="width: 7%;">
                        <col style="width: 10%;">
                        <col style="width: 10%;">
                    </colgroup>

                    <thead>
                        <tr class="border-b border-gray-300 bg-gray-100 text-left">
                            <th class="px-3 py-3 font-semibold">Description</th>
                            <th class="px-3 py-3 font-semibold">Type</th>
                            <th class="px-3 py-3 font-semibold">Qty</th>
                            <th class="px-3 py-3 font-semibold">Unit</th>
                            <th class="px-3 py-3 font-semibold">Unit amount</th>
                            <th class="px-3 py-3 font-semibold">Optional</th>
                            <th class="px-3 py-3 font-semibold">Subtotal</th>
                            <th class="px-3 py-3 font-semibold">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr class="border-b border-gray-300 bg-white align-top">
                            <td class="px-3 py-3">
                                <textarea
                                    form="add-line-item-form"
                                    name="description"
                                    rows="2"
                                    class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                    placeholder="Add new line item..."
                                    required
                                >{{ old('description') }}</textarea>
                            </td>

                            <td class="px-3 py-3">
                                <input
                                    form="add-line-item-form"
                                    name="type"
                                    type="text"
                                    value="{{ old('type', 'works') }}"
                                    class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                    required
                                >
                            </td>

                            <td class="px-3 py-3">
                                <input
                                    form="add-line-item-form"
                                    name="quantity"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    value="{{ old('quantity', '1') }}"
                                    class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                    required
                                >
                            </td>

                            <td class="px-3 py-3">
                                <input
                                    form="add-line-item-form"
                                    name="unit"
                                    type="text"
                                    value="{{ old('unit', 'item') }}"
                                    class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                    required
                                >
                            </td>

                            <td class="px-3 py-3">
                                <input
                                    form="add-line-item-form"
                                    name="unit_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value="{{ old('unit_amount', '0.00') }}"
                                    class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                    required
                                >
                            </td>

                            <td class="px-3 py-3">
                                <label class="inline-flex items-center gap-2 border border-gray-400 px-3 py-2 h-[38px]">
                                    <input
                                        form="add-line-item-form"
                                        type="checkbox"
                                        name="is_optional"
                                        value="1"
                                        @checked(old('is_optional'))
                                    >
                                </label>
                            </td>

                            <td class="px-3 py-3">
                                <div class="border border-gray-300 bg-gray-50 px-3 py-2 font-semibold text-gray-600">
                                    New
                                </div>
                            </td>

                            <td class="px-3 py-3">
                                <button
                                    form="add-line-item-form"
                                    type="submit"
                                    class="w-full px-4 py-2 bg-black text-white text-sm font-semibold rounded-none"
                                >
                                    Add
                                </button>
                            </td>
                        </tr>

                        @forelse ($lineItems as $lineItem)
                            @php
                                $unitAmountValue = number_format(($lineItem->unit_amount_pence ?? 0) / 100, 2, '.', '');
                                $lineTotalValue = number_format(($lineItem->total_pence ?? 0) / 100, 2);
                            @endphp

                            <tr class="border-b border-gray-200 bg-white align-top">
                                <td class="px-3 py-3">
                                    <textarea
                                        form="line-item-form-{{ $lineItem->id }}"
                                        name="description"
                                        rows="2"
                                        class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm font-semibold"
                                        required
                                    >{{ old('line_items.' . $lineItem->id . '.description', $lineItem->description) }}</textarea>

                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $lineItem->source ?: 'manual' }}
                                    </div>
                                </td>

                                <td class="px-3 py-3">
                                    <input
                                        form="line-item-form-{{ $lineItem->id }}"
                                        name="type"
                                        type="text"
                                        value="{{ old('line_items.' . $lineItem->id . '.type', $lineItem->type) }}"
                                        class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                        required
                                    >
                                </td>

                                <td class="px-3 py-3">
                                    <input
                                        form="line-item-form-{{ $lineItem->id }}"
                                        name="quantity"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        value="{{ old('line_items.' . $lineItem->id . '.quantity', $lineItem->quantity) }}"
                                        class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                        required
                                    >
                                </td>

                                <td class="px-3 py-3">
                                    <input
                                        form="line-item-form-{{ $lineItem->id }}"
                                        name="unit"
                                        type="text"
                                        value="{{ old('line_items.' . $lineItem->id . '.unit', $lineItem->unit) }}"
                                        class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                        required
                                    >
                                </td>

                                <td class="px-3 py-3">
                                    <input
                                        form="line-item-form-{{ $lineItem->id }}"
                                        name="unit_amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value="{{ old('line_items.' . $lineItem->id . '.unit_amount', $unitAmountValue) }}"
                                        class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                        required
                                    >
                                </td>

                                <td class="px-3 py-3">
                                    <label class="inline-flex items-center gap-2 border border-gray-400 px-3 py-2 h-[38px]">
                                        <input
                                            form="line-item-form-{{ $lineItem->id }}"
                                            type="checkbox"
                                            name="is_optional"
                                            value="1"
                                            @checked(old('line_items.' . $lineItem->id . '.is_optional', $lineItem->is_optional))
                                        >
                                    </label>
                                </td>

                                <td class="px-3 py-3">
                                    <div class="border border-gray-300 bg-gray-50 px-3 py-2 font-bold">
                                        £{{ $lineTotalValue }}
                                    </div>
                                </td>

                                <td class="px-3 py-3">
                                    <div class="grid grid-cols-1 gap-2">
                                        <button
                                            form="line-item-form-{{ $lineItem->id }}"
                                            type="submit"
                                            class="w-full px-4 py-2 bg-black text-white text-sm font-semibold rounded-none"
                                        >
                                            Save
                                        </button>

                                        <form method="POST" action="{{ route('admin.quotes.line-items.destroy', [$quote, $lineItem]) }}">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="w-full px-4 py-2 border border-red-700 text-red-700 text-sm font-semibold rounded-none"
                                                onclick="return confirm('Delete this line item?')"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-b border-gray-200 bg-white">
                                <td colspan="8" class="px-3 py-8 text-center text-gray-600">
                                    No line items have been added yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr class="bg-gray-50">
                            <td colspan="6" class="px-3 py-3 text-right font-semibold">Subtotal</td>
                            <td colspan="2" class="px-3 py-3 font-semibold">£{{ $quote->subtotal }}</td>
                        </tr>

                        <tr class="bg-gray-50">
                            <td colspan="6" class="px-3 py-3 text-right font-semibold">VAT</td>
                            <td colspan="2" class="px-3 py-3 font-semibold">£{{ $quote->vat }}</td>
                        </tr>

                        <tr class="bg-gray-50 border-t border-gray-300">
                            <td colspan="6" class="px-3 py-4 text-right text-lg font-bold">Total</td>
                            <td colspan="2" class="px-3 py-4 text-lg font-bold">£{{ $quote->total }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>