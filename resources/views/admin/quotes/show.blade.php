<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $quote->quote_number }}
            </h2>

            <a href="{{ route('admin.quotes.edit', $quote) }}"
               class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold">
                Edit quote details
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4">
        @if (session('status'))
            <div class="mb-6 border border-green-700 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-8">
            <a href="{{ route('admin.quotes.index') }}" class="text-sm underline">
                Back to quotes
            </a>

            <h1 class="text-3xl font-bold mt-4">
                {{ $quote->title }}
            </h1>

            <p class="text-gray-600 mt-2">
                {{ $quote->customer?->display_name }} — {{ $quote->quote_number }}
            </p>
        </div>

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
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="border border-gray-300 bg-white p-4">
                <div class="text-xs font-semibold text-gray-600">Status</div>
                <div class="mt-2">
                    <span class="inline-flex px-2 py-1 border text-xs font-semibold {{ $statusClass }}">
                        {{ ucfirst(str_replace('_', ' ', $quote->status)) }}
                    </span>
                </div>
            </div>

            <div class="border border-gray-300 bg-white p-4">
                <div class="text-xs font-semibold text-gray-600">Current total</div>
                <div class="text-2xl font-bold mt-2">£{{ $quote->total }}</div>
            </div>

            <div class="border border-gray-300 bg-white p-4">
                <div class="text-xs font-semibold text-gray-600">Assigned to</div>
                <div class="text-lg font-semibold mt-2">{{ $quote->assignedUser?->name ?: '—' }}</div>
            </div>

            <div class="border border-gray-300 bg-white p-4">
                <div class="text-xs font-semibold text-gray-600">Valid until</div>
                <div class="text-lg font-semibold mt-2">
                    {{ $quote->valid_until ? $quote->valid_until->format('d M Y') : '—' }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <section class="lg:col-span-2 border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Quote summary</h2>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-semibold text-gray-700">Customer</dt>
                        <dd>
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
                        <dd>{{ $quote->creator?->name ?: '—' }}</dd>
                    </div>
                </dl>

                @if ($quote->site_address)
                    <div class="mt-6">
                        <h3 class="font-semibold text-gray-700 text-sm mb-2">Site address</h3>
                        <p class="text-sm whitespace-pre-line">{{ $quote->site_address }}</p>
                    </div>
                @endif

                @if ($quote->summary)
                    <div class="mt-6">
                        <h3 class="font-semibold text-gray-700 text-sm mb-2">Summary</h3>
                        <p class="text-sm whitespace-pre-line">{{ $quote->summary }}</p>
                    </div>
                @endif
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Work areas</h2>

                <div class="space-y-3">
                    <a href="{{ route('admin.quotes.survey', $quote) }}"
                       class="block border border-blue-700 bg-blue-50 p-4 text-blue-900">
                        <div class="font-semibold">Survey mode</div>
                        <div class="text-sm mt-1">
                            Add walk-round notes, measurements and photos.
                        </div>
                    </a>

                    <a href="{{ route('admin.quotes.pricing', $quote) }}"
                       class="block border border-gray-900 bg-white p-4 text-gray-900">
                        <div class="font-semibold">Pricing</div>
                        <div class="text-sm mt-1">
                            Add and manage quote line items.
                        </div>
                    </a>

                    <a href="{{ route('admin.quotes.pack', $quote) }}"
                       class="block border border-yellow-700 bg-yellow-50 p-4 text-yellow-900">
                        <div class="font-semibold">Customer pack</div>
                        <div class="text-sm mt-1">
                            Edit customer-facing wording and quote sections.
                        </div>
                    </a>
                    <a href="{{ route('admin.quotes.download', $quote) }}"
                        class="block border border-green-700 bg-green-50 p-4 text-green-900">
                            <div class="font-semibold">Download quote PDF</div>
                            <div class="text-sm mt-1">
                                Generate a customer-facing quote PDF.
                            </div>
                        </a>
                </div>
            </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Survey</h2>

                <dl class="text-sm space-y-3">
                    <div>
                        <dt class="font-semibold text-gray-700">Notes</dt>
                        <dd>{{ $quote->notes()->count() }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Photos/files</dt>
                        <dd>{{ $quote->files()->count() }}</dd>
                    </div>
                </dl>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Pricing</h2>

                <dl class="text-sm space-y-3">
                    <div>
                        <dt class="font-semibold text-gray-700">Line items</dt>
                        <dd>{{ $quote->lineItems()->count() }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Total</dt>
                        <dd class="font-semibold">£{{ $quote->total }}</dd>
                    </div>
                </dl>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Follow-up</h2>

                @php
                    $nextFollowUp = $quote->followUps()
                        ->whereNull('completed_at')
                        ->orderBy('due_at')
                        ->first();
                @endphp

                @if ($nextFollowUp)
                    <p class="text-sm">
                        Next follow-up:
                        <strong>{{ $nextFollowUp->due_at->format('d M Y H:i') }}</strong>
                    </p>
                @else
                    <p class="text-sm text-gray-600">
                        No follow-up scheduled.
                    </p>
                @endif

                <p class="text-xs text-gray-500 mt-3">
                    Follow-ups will be focused around quote generation and sending.
                </p>
            </section>
        </div>
    </div>
</x-app-layout>