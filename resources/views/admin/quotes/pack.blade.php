<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Customer pack — {{ $quote->quote_number }}
            </h2>

            <a href="{{ route('admin.quotes.show', $quote) }}"
               class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold">
                Back to quote
            </a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4">
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
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Customer pack</h1>
            <p class="text-gray-600 mt-2">
                Edit the customer-facing quote wording. Use AI compile to draft wording from the survey notes, photos and pricing data.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.quotes.compile-ai', $quote) }}"
              onsubmit="return confirm('This will replace the current customer pack text and replace previous AI-suggested line items. Continue?')">
            @csrf

            <button type="submit"
                    class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold">
                Compile with AI
            </button>
        </form>
    </div>
</div>

        <form method="POST" action="{{ route('admin.quotes.pack.update', $quote) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Customer-facing sections</h2>

                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Customer message</label>
                        <textarea name="final_customer_message" rows="6" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('final_customer_message', $quote->final_customer_message) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Scope of works</label>
                        <textarea name="final_scope" rows="8" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('final_scope', $quote->final_scope) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Estimated timeline</label>
                        <textarea name="final_timeline" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('final_timeline', $quote->final_timeline) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Assumptions</label>
                        <textarea name="final_assumptions" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('final_assumptions', $quote->final_assumptions) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Exclusions</label>
                        <textarea name="final_exclusions" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('final_exclusions', $quote->final_exclusions) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Terms</label>
                        <textarea name="final_terms" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ old('final_terms', $quote->final_terms) }}</textarea>
                    </div>
                </div>
            </section>

            <div class="flex items-center gap-4">
                <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold">
                    Save customer pack
                </button>

                <a href="{{ route('admin.quotes.show', $quote) }}" class="text-sm underline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-app-layout>