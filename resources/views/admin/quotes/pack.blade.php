<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Customer pack — {{ $quote->quote_number }}
                </h2>

                <p class="text-sm text-gray-600 mt-1">
                    Edit the customer-facing wording before downloading or sending the quote.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.quotes.pricing', $quote) }}"
                   class="inline-flex px-5 py-3 border border-black text-sm font-semibold rounded-none">
                    Estimate assistant
                </a>

                <a href="{{ route('admin.quotes.download', $quote) }}"
                   class="inline-flex px-5 py-3 border border-black text-sm font-semibold rounded-none">
                    Download PDF
                </a>

                <a href="{{ route('admin.quotes.show', $quote) }}"
                   class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Back to quote
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4 space-y-8">
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

        <section class="border border-gray-300 bg-white p-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-gray-600">
                Customer quote pack
            </p>

            <h1 class="text-3xl font-bold mt-2">
                Final customer wording
            </h1>

            <p class="text-gray-700 mt-3">
                This page is only for the customer-facing text that appears in the quote pack and PDF.
                Use the Estimate Assistant on the pricing page to generate or review AI-suggested pricing items.
            </p>

            <div class="mt-5 border border-blue-700 bg-blue-50 p-4">
                <h2 class="font-semibold text-blue-900">
                    Need AI help?
                </h2>

                <p class="text-sm text-blue-900 mt-1">
                    Go to the Estimate Assistant to add job context, review suggested pricing items, and apply accepted items to the quote.
                    Once the wording is ready, return here to edit the final customer pack.
                </p>

                <a href="{{ route('admin.quotes.pricing', $quote) }}"
                   class="inline-flex mt-3 px-4 py-2 bg-black text-white text-sm font-semibold rounded-none">
                    Open Estimate Assistant
                </a>
            </div>
        </section>

        <form method="POST" action="{{ route('admin.quotes.pack.update', $quote) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">
                    Customer-facing sections
                </h2>

                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Customer message
                        </label>

                        <textarea
                            name="final_customer_message"
                            rows="6"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        >{{ old('final_customer_message', $quote->final_customer_message) }}</textarea>

                        <p class="text-xs text-gray-600 mt-1">
                            A short introduction to the customer. Keep this polite, clear and practical.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Scope of works
                        </label>

                        <textarea
                            name="final_scope"
                            rows="9"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        >{{ old('final_scope', $quote->final_scope) }}</textarea>

                        <p class="text-xs text-gray-600 mt-1">
                            Describe what is included in the quote. Avoid internal AI notes or estimating reasoning.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Estimated timeline
                        </label>

                        <textarea
                            name="final_timeline"
                            rows="5"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        >{{ old('final_timeline', $quote->final_timeline) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Assumptions
                        </label>

                        <textarea
                            name="final_assumptions"
                            rows="6"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        >{{ old('final_assumptions', $quote->final_assumptions) }}</textarea>

                        <p class="text-xs text-gray-600 mt-1">
                            List the assumptions the quote relies on, such as access, specification, working hours and site conditions.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Exclusions
                        </label>

                        <textarea
                            name="final_exclusions"
                            rows="6"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        >{{ old('final_exclusions', $quote->final_exclusions) }}</textarea>

                        <p class="text-xs text-gray-600 mt-1">
                            Clearly list anything not included, such as hidden defects, specialist surveys, utility upgrades or customer-supplied items.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Terms
                        </label>

                        <textarea
                            name="final_terms"
                            rows="6"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        >{{ old('final_terms', $quote->final_terms) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">
                    Quote totals
                </h2>

                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div class="border border-gray-300 p-4">
                        <dt class="text-gray-600">Subtotal</dt>
                        <dd class="text-xl font-bold mt-1">£{{ $quote->subtotal }}</dd>
                    </div>

                    <div class="border border-gray-300 p-4">
                        <dt class="text-gray-600">VAT</dt>
                        <dd class="text-xl font-bold mt-1">£{{ $quote->vat }}</dd>
                    </div>

                    <div class="border border-gray-300 p-4">
                        <dt class="text-gray-600">Total</dt>
                        <dd class="text-xl font-bold mt-1">£{{ $quote->total }}</dd>
                    </div>
                </dl>
            </section>

            <div class="flex flex-wrap items-center gap-4">
                <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Save customer pack
                </button>

                <a href="{{ route('admin.quotes.download', $quote) }}"
                   class="px-5 py-3 border border-black text-sm font-semibold rounded-none">
                    Download PDF
                </a>

                <a href="{{ route('admin.quotes.show', $quote) }}" class="text-sm underline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-app-layout>