<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pricing — {{ $quote->quote_number }}</h2>
            <div class="flex gap-3">
                <a href="{{ route('admin.pricing-settings.edit') }}" class="inline-flex px-5 py-3 border border-black text-sm font-semibold rounded-none">AI & pricing settings</a>
                <a href="{{ route('admin.quotes.show', $quote) }}" class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">Back to quote</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4 space-y-8">
        @if (session('status'))
            <div class="border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">{{ session('status') }}</div>
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

        <section class="border border-gray-300 bg-white p-6">
            <h1 class="text-3xl font-bold">Pricing command centre</h1>
            <p class="text-gray-600 mt-2 max-w-3xl">
                Generate an AI pricing draft from the survey context, then review, edit and accept the draft before applying it to the quote total.
            </p>
        </section>

        <section class="border border-gray-300 bg-white p-6">
            <h2 class="text-lg font-semibold mb-4">Generate AI pricing draft</h2>
            <form method="POST" action="{{ route('admin.quotes.compile-ai', $quote) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold mb-2">Extra pricing guidance, optional</label>
                    <textarea name="pricing_hint" rows="4" class="w-full border border-gray-400 px-4 py-3 rounded-none" placeholder="Example: customer is supplying tiles; allow one 6-yard skip; access is difficult; exclude electrical works.">{{ old('pricing_hint') }}</textarea>
                    <p class="text-sm text-gray-600 mt-1">Use this for context and existing pricing hints. AI will use it to select rate-card items and quantities, not to bypass the rate card.</p>
                </div>
                <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">Generate AI pricing draft</button>
            </form>
        </section>

        @if ($latestDraft)
            <section class="border border-gray-300 bg-white p-6">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-semibold">Latest AI pricing draft</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Job type: <span class="font-semibold">{{ $latestDraft->detected_job_type ?: 'Not detected' }}</span>
                            · Template: <span class="font-semibold">{{ $latestDraft->selected_template_code ?: 'Not selected' }}</span>
                            · Confidence: <span class="font-semibold">{{ ucfirst($latestDraft->overall_confidence) }}</span>
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <form method="POST" action="{{ route('admin.quotes.ai-drafts.apply-accepted', [$quote, $latestDraft]) }}">
                            @csrf
                            <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none" onclick="return confirm('Apply accepted AI draft items to this quote?')">
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
                        <h3 class="font-semibold text-yellow-900">Missing information before sending</h3>
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
                        <h3 class="font-semibold text-red-900">Pricing warnings</h3>
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
                                <th class="px-3 py-2">Item</th>
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
                                <tr class="border-b border-gray-200 align-top">
                                    <td class="px-3 py-3 min-w-80">
                                        <form id="draft-item-{{ $item->id }}" method="POST" action="{{ route('admin.quotes.ai-draft-items.update', [$quote, $item]) }}" class="space-y-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="pricing_rate_item_id" class="w-full border border-gray-400 px-3 py-2 rounded-none text-xs">
                                                @foreach ($rateItems as $rateItem)
                                                    <option value="{{ $rateItem->id }}" @selected($item->pricing_rate_item_id === $rateItem->id)>
                                                        {{ $rateItem->code }} — {{ $rateItem->name }} (£{{ $rateItem->base_cost }}/{{ $rateItem->unit }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input name="clean_customer_description" value="{{ $item->clean_customer_description }}" class="w-full border border-gray-400 px-3 py-2 rounded-none text-xs" required>
                                            <textarea name="warnings" rows="2" class="w-full border border-gray-400 px-3 py-2 rounded-none text-xs" placeholder="Internal warnings">{{ implode("\n", $item->warnings ?? []) }}</textarea>
                                        </form>
                                        <p class="text-xs text-gray-500 mt-2 font-mono">{{ $item->rate_item_code }}</p>
                                        @if ($item->internal_reasoning)
                                            <p class="text-xs text-gray-600 mt-2">{{ $item->internal_reasoning }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <input form="draft-item-{{ $item->id }}" name="quantity" type="number" step="0.01" min="0.01" value="{{ $item->quantity }}" class="w-24 border border-gray-400 px-2 py-1 rounded-none text-xs" required>
                                        <div class="text-xs text-gray-500 mt-1">{{ $item->unit }}</div>
                                    </td>
                                    <td class="px-3 py-3">£{{ $item->base_total }}</td>
                                    <td class="px-3 py-3">{{ $item->markup_percent }}%</td>
                                    <td class="px-3 py-3">£{{ $item->subtotal }}</td>
                                    <td class="px-3 py-3">£{{ $item->vat }}</td>
                                    <td class="px-3 py-3 font-semibold">£{{ $item->total }}</td>
                                    <td class="px-3 py-3">
                                        <select form="draft-item-{{ $item->id }}" name="confidence" class="border border-gray-400 px-2 py-1 rounded-none text-xs">
                                            <option value="low" @selected($item->confidence === 'low')>Low</option>
                                            <option value="medium" @selected($item->confidence === 'medium')>Medium</option>
                                            <option value="high" @selected($item->confidence === 'high')>High</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-3">{{ ucfirst($item->status) }}</td>
                                    <td class="px-3 py-3 space-y-2">
                                        <button form="draft-item-{{ $item->id }}" type="submit" class="block w-full px-3 py-2 bg-black text-white text-xs font-semibold rounded-none">Save + accept</button>
                                        <form method="POST" action="{{ route('admin.quotes.ai-draft-items.accept', [$quote, $item]) }}">
                                            @csrf
                                            <button type="submit" class="block w-full px-3 py-2 border border-black text-xs font-semibold rounded-none">Accept</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.quotes.ai-draft-items.reject', [$quote, $item]) }}">
                                            @csrf
                                            <button type="submit" class="block w-full px-3 py-2 border border-red-700 text-red-700 text-xs font-semibold rounded-none">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="px-3 py-8 text-center text-gray-600">This AI draft does not contain priced items. Check rate-card coverage and pricing warnings.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Quote line items</h2>
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
                                        <div class="text-xs text-gray-500 mt-1">{{ ucfirst(str_replace('_', ' ', $lineItem->type)) }} · {{ $lineItem->source }} @if ($lineItem->is_optional) · Optional @endif</div>
                                    </td>
                                    <td class="px-3 py-3">{{ $lineItem->quantity }}</td>
                                    <td class="px-3 py-3">£{{ $lineItem->unit_amount }} / {{ $lineItem->unit }}</td>
                                    <td class="px-3 py-3 font-semibold">£{{ $lineItem->total }}</td>
                                    <td class="px-3 py-3">
                                        <form method="POST" action="{{ route('admin.quotes.line-items.destroy', [$quote, $lineItem]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs underline text-red-700" onclick="return confirm('Delete this line item?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-8 text-center text-gray-600">No line items have been added yet.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr><th colspan="3" class="px-3 py-3 text-right">Subtotal</th><th class="px-3 py-3 text-left">£{{ $quote->subtotal }}</th><th></th></tr>
                            <tr><th colspan="3" class="px-3 py-3 text-right">VAT</th><th class="px-3 py-3 text-left">£{{ $quote->vat }}</th><th></th></tr>
                            <tr><th colspan="3" class="px-3 py-3 text-right">Total</th><th class="px-3 py-3 text-left">£{{ $quote->total }}</th><th></th></tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Add manual line item</h2>
                <form method="POST" action="{{ route('admin.quotes.line-items.store', $quote) }}" class="space-y-4">
                    @csrf
                    <div><label class="block text-sm font-semibold mb-2">Type</label><input name="type" type="text" value="works" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                    <div><label class="block text-sm font-semibold mb-2">Description</label><input name="description" type="text" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                    <div><label class="block text-sm font-semibold mb-2">Quantity</label><input name="quantity" type="number" min="0.01" step="0.01" value="1" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                    <div><label class="block text-sm font-semibold mb-2">Unit</label><input name="unit" type="text" value="item" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                    <div><label class="block text-sm font-semibold mb-2">Unit amount before VAT</label><input name="unit_amount" type="number" min="0" step="0.01" value="0.00" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_optional" value="1"> Optional item</label>
                    <button type="submit" class="w-full px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">Add line item</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
