<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Quote {{ $quote->quote_number }}
                </h2>

                <p class="text-sm text-gray-600 mt-1">
                    {{ $quote->title ?: 'Untitled quote' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.quotes.index') }}"
                   class="inline-flex px-5 py-3 border border-black text-sm font-semibold rounded-none">
                    Back to quotes
                </a>

                <a href="{{ route('admin.quotes.edit', $quote) }}"
                   class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Edit quote details
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $statusClass = match ($quote->status) {
            'draft' => 'border-gray-500 bg-gray-50 text-gray-800',
            'survey_in_progress' => 'border-blue-700 bg-blue-50 text-blue-900',
            'survey_completed', 'ai_compiled' => 'border-yellow-700 bg-yellow-50 text-yellow-900',
            'sent' => 'border-purple-700 bg-purple-50 text-purple-900',
            'accepted' => 'border-green-700 bg-green-50 text-green-900',
            'declined', 'expired', 'cancelled' => 'border-red-700 bg-red-50 text-red-900',
            default => 'border-gray-400 bg-white text-gray-700',
        };

        $notesCount = $quote->notes()->count();
        $filesCount = $quote->files()->count();
        $lineItemsCount = $quote->lineItems()->count();

        $nextFollowUp = $quote->followUps()
            ->whereNull('completed_at')
            ->orderBy('due_at')
            ->first();
    @endphp

    <div class="max-w-7xl mx-auto py-8 px-4 space-y-6">
        @if (session('status'))
            <div class="border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        <section class="border border-gray-300 bg-white">
            <div class="p-6 border-b border-gray-300">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div>
                        <div class="mb-3">
                            <span class="inline-flex px-2 py-1 border text-xs font-semibold {{ $statusClass }}">
                                {{ ucfirst(str_replace('_', ' ', $quote->status)) }}
                            </span>
                        </div>

                        <h1 class="text-3xl font-bold text-gray-900">
                            {{ $quote->title ?: 'Untitled quote' }}
                        </h1>

                        <p class="text-gray-600 mt-2">
                            {{ $quote->customer?->display_name ?: 'No customer selected' }} — {{ $quote->quote_number }}
                        </p>

                        @if ($quote->site_address)
                            <p class="text-sm text-gray-700 mt-4 whitespace-pre-line">
                                {{ $quote->site_address }}
                            </p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-2 gap-3 w-full lg:w-[420px]">
                        <div class="border border-gray-300 bg-gray-50 p-4">
                            <div class="text-xs font-semibold text-gray-600">Total</div>
                            <div class="text-2xl font-bold mt-1">£{{ $quote->total }}</div>
                        </div>

                        <div class="border border-gray-300 bg-gray-50 p-4">
                            <div class="text-xs font-semibold text-gray-600">Line items</div>
                            <div class="text-2xl font-bold mt-1">{{ $lineItemsCount }}</div>
                        </div>

                        <div class="border border-gray-300 bg-gray-50 p-4">
                            <div class="text-xs font-semibold text-gray-600">Survey notes</div>
                            <div class="text-2xl font-bold mt-1">{{ $notesCount }}</div>
                        </div>

                        <div class="border border-gray-300 bg-gray-50 p-4">
                            <div class="text-xs font-semibold text-gray-600">Photos/files</div>
                            <div class="text-2xl font-bold mt-1">{{ $filesCount }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <a href="{{ route('admin.quotes.survey', $quote) }}"
                       class="group border border-gray-300 bg-white hover:border-black focus:border-black focus:outline-none p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-xs font-semibold text-gray-500">Step 1</div>
                                <h2 class="text-lg font-bold mt-1 text-gray-900">
                                    Survey
                                </h2>
                            </div>

                            <span class="text-sm font-semibold text-gray-500 group-hover:text-black">
                                Open
                            </span>
                        </div>

                        <p class="text-sm text-gray-600 mt-3">
                            Add walk-round notes, measurements and photos.
                        </p>

                        <div class="mt-4 text-xs text-gray-500">
                            {{ $notesCount }} notes · {{ $filesCount }} files
                        </div>
                    </a>

                    <a href="{{ route('admin.quotes.pricing', $quote) }}"
                       class="group border border-gray-300 bg-white hover:border-black focus:border-black focus:outline-none p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-xs font-semibold text-gray-500">Step 2</div>
                                <h2 class="text-lg font-bold mt-1 text-gray-900">
                                    Pricing
                                </h2>
                            </div>

                            <span class="text-sm font-semibold text-gray-500 group-hover:text-black">
                                Open
                            </span>
                        </div>

                        <p class="text-sm text-gray-600 mt-3">
                            Add, edit and manage quote line items.
                        </p>

                        <div class="mt-4 text-xs text-gray-500">
                            {{ $lineItemsCount }} line items · £{{ $quote->total }}
                        </div>
                    </a>

                    <a href="{{ route('admin.quotes.pack', $quote) }}"
                       class="group border border-gray-300 bg-white hover:border-black focus:border-black focus:outline-none p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-xs font-semibold text-gray-500">Step 3</div>
                                <h2 class="text-lg font-bold mt-1 text-gray-900">
                                    Customer pack
                                </h2>
                            </div>

                            <span class="text-sm font-semibold text-gray-500 group-hover:text-black">
                                Open
                            </span>
                        </div>

                        <p class="text-sm text-gray-600 mt-3">
                            Edit customer-facing wording and select PDF photos.
                        </p>

                        <div class="mt-4 text-xs text-gray-500">
                            Requirements, scope, assumptions and terms
                        </div>
                    </a>

                    <a href="{{ route('admin.quotes.download', $quote) }}"
                       class="group border border-black bg-black text-white hover:bg-white hover:text-black focus:outline-none p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-xs font-semibold opacity-80">Step 4</div>
                                <h2 class="text-lg font-bold mt-1">
                                    Download PDF
                                </h2>
                            </div>

                            <span class="text-sm font-semibold opacity-80">
                                Generate
                            </span>
                        </div>

                        <p class="text-sm mt-3 opacity-90">
                            Download the customer-facing quote pack.
                        </p>

                        <div class="mt-4 text-xs opacity-80">
                            Uses pack settings, wording, pricing and selected photos
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 border border-gray-300 bg-white">
                <div class="p-6 border-b border-gray-300">
                    <h2 class="text-lg font-bold">
                        Quote details
                    </h2>
                </div>

                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                        <div>
                            <dt class="font-semibold text-gray-700">Customer</dt>
                            <dd class="mt-1">
                                @if ($quote->customer)
                                    <a href="{{ route('admin.customers.show', $quote->customer) }}" class="underline">
                                        {{ $quote->customer->display_name }}
                                    </a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="font-semibold text-gray-700">Created by</dt>
                            <dd class="mt-1">{{ $quote->creator?->name ?: '—' }}</dd>
                        </div>

                        <div>
                            <dt class="font-semibold text-gray-700">Assigned to</dt>
                            <dd class="mt-1">{{ $quote->assignedUser?->name ?: '—' }}</dd>
                        </div>

                        <div>
                            <dt class="font-semibold text-gray-700">Valid until</dt>
                            <dd class="mt-1">
                                {{ $quote->valid_until ? $quote->valid_until->format('d M Y') : '—' }}
                            </dd>
                        </div>
                    </dl>

                    @if ($quote->summary)
                        <div class="mt-6 border-t border-gray-300 pt-6">
                            <h3 class="font-semibold text-gray-700 text-sm mb-2">
                                Summary
                            </h3>

                            <p class="text-sm whitespace-pre-line">
                                {{ $quote->summary }}
                            </p>
                        </div>
                    @endif

                    @if ($quote->internal_notes)
                        <div class="mt-6 border-t border-gray-300 pt-6">
                            <h3 class="font-semibold text-gray-700 text-sm mb-2">
                                Internal notes
                            </h3>

                            <p class="text-sm whitespace-pre-line">
                                {{ $quote->internal_notes }}
                            </p>
                        </div>
                    @endif
                </div>
            </section>

            <aside class="space-y-6">
                <section class="border border-gray-300 bg-white">
                    <div class="p-6 border-b border-gray-300">
                        <h2 class="text-lg font-bold">
                            Follow-up
                        </h2>
                    </div>

                    <div class="p-6">
                        @if ($nextFollowUp)
                            <p class="text-sm">
                                Next follow-up:
                            </p>

                            <p class="font-semibold mt-1">
                                {{ $nextFollowUp->due_at->format('d M Y H:i') }}
                            </p>
                        @else
                            <p class="text-sm text-gray-600">
                                No follow-up scheduled.
                            </p>
                        @endif

                        <p class="text-xs text-gray-500 mt-3">
                            Follow-ups are mainly used after quote generation or sending.
                        </p>
                    </div>
                </section>

                <section class="border border-gray-300 bg-white">
                    <div class="p-6 border-b border-gray-300">
                        <h2 class="text-lg font-bold">
                            Quick actions
                        </h2>
                    </div>

                    <div class="p-6 space-y-3">
                        <a href="{{ route('admin.quotes.edit', $quote) }}"
                           class="block w-full px-5 py-3 border border-black text-sm font-semibold rounded-none text-center">
                            Edit quote details
                        </a>

                        <a href="{{ route('admin.quotes.survey', $quote) }}"
                           class="block w-full px-5 py-3 border border-black text-sm font-semibold rounded-none text-center">
                            Add survey note
                        </a>

                        <a href="{{ route('admin.quotes.pricing', $quote) }}"
                           class="block w-full px-5 py-3 border border-black text-sm font-semibold rounded-none text-center">
                            Edit pricing
                        </a>

                        <a href="{{ route('admin.quotes.pack', $quote) }}"
                           class="block w-full px-5 py-3 border border-black text-sm font-semibold rounded-none text-center">
                            Edit customer pack
                        </a>

                        @if (Route::has('admin.settings.quote-pack.edit'))
                            <a href="{{ route('admin.settings.quote-pack.edit') }}"
                               class="block w-full px-5 py-3 border border-black text-sm font-semibold rounded-none text-center">
                                Pack settings
                            </a>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>