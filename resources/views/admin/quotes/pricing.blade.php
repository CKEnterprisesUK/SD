<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Pricing — {{ $quote->quote_number }}
            </h2>

            <a href="{{ route('admin.quotes.show', $quote) }}"
               class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold">
                Back to quote
            </a>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
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

        <div class="mb-8">
            <h1 class="text-3xl font-bold">Pricing</h1>
            <p class="text-gray-600 mt-2">
                Add and manage manual line items for this quote.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Line items</h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-300 bg-gray-50 text-left">
                                <th class="px-3 py-2 font-semibold">Description</th>
                                <th class="px-3 py-2 font-semibold">Qty</th>
                                <th class="px-3 py-2 font-semibold">Unit</th>
                                <th class="px-3 py-2 font-semibold">Total</th>
                                <th class="px-3 py-2 font-semibold">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($quote->lineItems as $lineItem)
                                <tr class="border-b border-gray-200">
                                    <td class="px-3 py-3">
                                        <div class="font-semibold">{{ $lineItem->description }}</div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ ucfirst($lineItem->type) }}
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

                                            <button type="submit"
                                                    class="text-xs underline text-red-700"
                                                    onclick="return confirm('Delete this line item?')">
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
                                <th colspan="3" class="px-3 py-3 text-right">Total</th>
                                <th class="px-3 py-3 text-left">£{{ $quote->total }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Add line item</h2>

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
                        <label class="block text-sm font-semibold mb-2">Unit amount</label>
                        <input name="unit_amount" type="number" min="0" step="0.01" value="0.00" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_optional" value="1">
                        Optional item
                    </label>

                    <button type="submit" class="w-full px-5 py-3 bg-black text-white text-sm font-semibold">
                        Add line item
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>