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

        $latestDraftItemsToApply = collect();

        if ($latestDraft && $latestDraft->relationLoaded('items')) {
            $latestDraftItemsToApply = $latestDraft->items
                ->filter(fn ($item) => ! in_array($item->status, ['rejected', 'applied'], true))
                ->values();
        }

        $lineItems = $quote->lineItems ?? collect();
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

                    <p class="text-sm text-gray-600 mt-2 max-w-2xl">
                        Edit the final quote line items below. These are the items used in the customer quote total.
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
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">
                            Latest AI estimate
                        </h2>

                        <p class="text-sm text-gray-600 mt-1">
                            {{ $latestDraft->detected_job_type ?: 'Job type not detected' }}
                            · Confidence: {{ ucfirst($latestDraft->overall_confidence) }}
                            · {{ $latestDraftItemsToApply->count() }} item{{ $latestDraftItemsToApply->count() === 1 ? '' : 's' }} ready to add
                        </p>
                    </div>

                    @if ($latestDraftItemsToApply->isNotEmpty())
                        <form method="POST" action="{{ route('admin.quotes.ai-drafts.apply-accepted', [$quote, $latestDraft]) }}">
                            @csrf

                            <button
                                type="submit"
                                class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none"
                                onclick="return confirm('Add the AI estimate items to this quote? You can edit them afterwards.')"
                            >
                                Add AI estimate to line items
                            </button>
                        </form>
                    @else
                        <div class="border border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                            No AI estimate items waiting to be added.
                        </div>
                    @endif
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
                                These are the final priced rows used in the quote.
                            </p>
                        </div>

                        <div class="border border-gray-300 bg-gray-50 px-4 py-3 text-sm">
                            <div class="font-semibold">Quote total</div>
                            <div class="text-xl font-bold">£{{ $quote->total }}</div>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    @forelse ($lineItems as $lineItem)
                        @php
                            $unitAmountValue = number_format(($lineItem->unit_amount_pence ?? 0) / 100, 2, '.', '');
                            $lineTotalValue = number_format(($lineItem->total_pence ?? 0) / 100, 2);
                        @endphp

                        <article class="border border-gray-300 bg-white">
                            <form method="POST"
                                  action="{{ route('admin.quotes.line-items.update', [$quote, $lineItem]) }}"
                                  class="p-4 space-y-4">
                                @csrf
                                @method('PUT')

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
                                        >{{ old('line_items.' . $lineItem->id . '.description', $lineItem->description) }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold mb-1">
                                            Subtotal
                                        </label>

                                        <div class="border border-gray-300 bg-gray-50 px-3 py-2 text-lg font-bold">
                                            £{{ $lineTotalValue }}
                                        </div>

                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $lineItem->source ?: 'manual' }}
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
                                            value="{{ old('line_items.' . $lineItem->id . '.unit_amount', $unitAmountValue) }}"
                                            class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                            required
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
                                            >

                                            Yes
                                        </label>
                                    </div>

                                    <div class="flex items-end">
                                        <button
                                            type="submit"
                                            class="w-full px-4 py-2 bg-black text-white text-sm font-semibold rounded-none"
                                        >
                                            Save
                                        </button>
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
            </aside>
        </div>
    </div>
</x-app-layout>