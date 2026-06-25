<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Customer pack — {{ $quote->quote_number }}
                </h2>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.quotes.pricing', $quote) }}"
                   class="inline-flex px-5 py-3 border border-black text-sm font-semibold rounded-none">
                    Pricing
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
                        Customer wording
                    </h1>

                    <p class="text-sm text-gray-600 mt-2 max-w-3xl">
                        Generate the customer-facing wording from the survey notes, then review and save it.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.quotes.generate-ai-wording', $quote) }}" class="lg:w-[520px] space-y-3">
                    @csrf

                    <textarea
                        name="wording_hint"
                        rows="3"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                        placeholder="Optional note: make this sound premium, mention customer wants a standard bathroom finish, avoid mentioning electrics..."
                    >{{ old('wording_hint') }}</textarea>

                    <button type="submit" class="w-full px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                        Generate customer wording
                    </button>
                </form>
            </div>
        </section>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <section class="xl:col-span-2 border border-gray-300 bg-white">
                <div class="p-6 border-b border-gray-300">
                    <h2 class="text-lg font-semibold">
                        Quote pack wording
                    </h2>
                </div>

                <form method="POST" action="{{ route('admin.quotes.pack.update', $quote) }}" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Opening message
                        </label>

                        <textarea
                            name="final_customer_message"
                            rows="4"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                        >{{ old('final_customer_message', $quote->final_customer_message) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Site visit summary
                        </label>

                        <textarea
                            name="final_site_visit_summary"
                            rows="5"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                            placeholder="Example: On 12 June 2026, the customer was visited to discuss..."
                        >{{ old('final_site_visit_summary', $quote->final_site_visit_summary ?? '') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Current setup
                        </label>

                        <textarea
                            name="final_existing_setup"
                            rows="5"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                            placeholder="Describe the existing bathroom, kitchen, extension area, roof, room or site condition."
                        >{{ old('final_existing_setup', $quote->final_existing_setup ?? '') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Measurements
                        </label>

                        <textarea
                            name="final_measurements_summary"
                            rows="4"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                            placeholder="Room sizes, wall areas, floor areas, opening sizes, heights or measurements to be confirmed."
                        >{{ old('final_measurements_summary', $quote->final_measurements_summary ?? '') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Customer requirements
                        </label>

                        <textarea
                            name="final_customer_requirements"
                            rows="6"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                            placeholder="Explain what the customer wants to achieve."
                        >{{ old('final_customer_requirements', $quote->final_customer_requirements ?? '') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Customer preferences and assumptions
                        </label>

                        <textarea
                            name="final_preferences_assumptions"
                            rows="5"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                            placeholder="Preferences discussed and assumptions made."
                        >{{ old('final_preferences_assumptions', $quote->final_preferences_assumptions ?? '') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Proposed scope of works
                        </label>

                        <textarea
                            name="final_scope"
                            rows="8"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                        >{{ old('final_scope', $quote->final_scope) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Timeline
                        </label>

                        <textarea
                            name="final_timeline"
                            rows="3"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                        >{{ old('final_timeline', $quote->final_timeline) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold mb-2">
                                Assumptions
                            </label>

                            <textarea
                                name="final_assumptions"
                                rows="6"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                            >{{ old('final_assumptions', $quote->final_assumptions) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">
                                Exclusions
                            </label>

                            <textarea
                                name="final_exclusions"
                                rows="6"
                                class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                            >{{ old('final_exclusions', $quote->final_exclusions) }}</textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Terms / next steps
                        </label>

                        <textarea
                            name="final_terms"
                            rows="5"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none text-sm"
                        >{{ old('final_terms', $quote->final_terms) }}</textarea>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                            Save customer pack
                        </button>

                        <a href="{{ route('admin.quotes.download', $quote) }}"
                           class="inline-flex px-5 py-3 border border-black text-sm font-semibold rounded-none">
                            Download PDF
                        </a>
                    </div>
                </form>
            </section>

            <aside class="space-y-6">
                <section class="border border-gray-300 bg-white p-6">
                    <h2 class="text-lg font-semibold">
                        Quote summary
                    </h2>

                    <dl class="mt-4 text-sm space-y-3">
                        <div>
                            <dt class="font-semibold">Customer</dt>
                            <dd class="text-gray-700">{{ $quote->customer?->display_name ?: 'Not set' }}</dd>
                        </div>

                        <div>
                            <dt class="font-semibold">Project</dt>
                            <dd class="text-gray-700">{{ $quote->title ?: 'Untitled quote' }}</dd>
                        </div>

                        <div>
                            <dt class="font-semibold">Site address</dt>
                            <dd class="text-gray-700 whitespace-pre-line">{{ $quote->site_address ?: 'Not set' }}</dd>
                        </div>

                        <div>
                            <dt class="font-semibold">Total</dt>
                            <dd class="text-gray-900 font-bold">£{{ $quote->total }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="border border-gray-300 bg-white">
                    <div class="p-6 border-b border-gray-300">
                        <h2 class="text-lg font-semibold">
                            Quote-pack photos
                        </h2>
                    </div>

                    <form method="POST" action="{{ route('admin.quotes.pack.photos.update', $quote) }}" class="p-6 space-y-4">
                        @csrf
                        @method('PUT')

                        @forelse ($photos as $photo)
                            <div class="border border-gray-300 p-3">
                                <div class="aspect-[4/3] bg-gray-100 border border-gray-200 overflow-hidden">
                                    <img
                                        src="{{ $photo->url }}"
                                        alt="{{ $photo->original_name }}"
                                        class="w-full h-full object-cover"
                                    >
                                </div>

                                <label class="mt-3 flex items-center gap-2 text-sm font-semibold">
                                    <input
                                        type="checkbox"
                                        name="photos[{{ $photo->id }}][include]"
                                        value="1"
                                        @checked($photo->include_in_quote_pack)
                                    >

                                    Include in PDF
                                </label>

                                <div class="mt-3">
                                    <label class="block text-xs font-semibold mb-1">
                                        Caption
                                    </label>

                                    <input
                                        type="text"
                                        name="photos[{{ $photo->id }}][caption]"
                                        value="{{ old('photos.' . $photo->id . '.caption', $photo->quote_pack_caption ?: $photo->caption) }}"
                                        class="w-full border border-gray-400 px-3 py-2 rounded-none text-sm"
                                    >
                                </div>

                                <div class="mt-3">
                                    <label class="block text-xs font-semibold mb-1">
                                        Sort
                                    </label>

                                    <input
                                        type="number"
                                        min="0"
                                        max="999"
                                        name="photos[{{ $photo->id }}][sort_order]"
                                        value="{{ old('photos.' . $photo->id . '.sort_order', $photo->quote_pack_sort_order ?? 0) }}"
                                        class="w-24 border border-gray-400 px-3 py-2 rounded-none text-sm"
                                    >
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-600">
                                No survey photos have been uploaded yet.
                            </p>
                        @endforelse

                        @if ($photos->isNotEmpty())
                            <button type="submit" class="w-full px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                                Save photos
                            </button>
                        @endif
                    </form>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>