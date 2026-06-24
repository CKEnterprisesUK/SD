<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $quote->quote_number }}
            </h2>

            <a href="{{ route('admin.quotes.edit', $quote) }}"
               class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold">
                Edit quote
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4">
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <section class="lg:col-span-2 border border-gray-300 bg-white p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold">Quote details</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Core quote information and customer relationship.
                        </p>
                    </div>

                    <span class="inline-flex px-2 py-1 border text-xs font-semibold {{ $statusClass }}">
                        {{ ucfirst(str_replace('_', ' ', $quote->status)) }}
                    </span>
                </div>

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
                        <dt class="font-semibold text-gray-700">Assigned to</dt>
                        <dd>{{ $quote->assignedUser?->name ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Created by</dt>
                        <dd>{{ $quote->creator?->name ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Valid until</dt>
                        <dd>{{ $quote->valid_until ? $quote->valid_until->format('d M Y') : '—' }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Subtotal</dt>
                        <dd>£{{ $quote->subtotal }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-gray-700">Total</dt>
                        <dd class="font-semibold">£{{ $quote->total }}</dd>
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
                <h2 class="text-lg font-semibold mb-4">Actions</h2>

                <div class="space-y-3">
                    @if ($quote->status === 'draft')
                        <form method="POST" action="{{ route('admin.quotes.mark-survey-in-progress', $quote) }}">
                            @csrf

                            <button type="submit"
                                    class="w-full px-4 py-3 border border-blue-700 bg-blue-50 text-blue-900 text-sm font-semibold text-left">
                                Start survey
                            </button>
                        </form>
                    @endif

                    @if (in_array($quote->status, ['draft', 'survey_in_progress']))
                        <form method="POST" action="{{ route('admin.quotes.mark-survey-completed', $quote) }}">
                            @csrf

                            <button type="submit"
                                    class="w-full px-4 py-3 border border-yellow-700 bg-yellow-50 text-yellow-900 text-sm font-semibold text-left">
                                Mark survey completed
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('admin.quotes.edit', $quote) }}"
                       class="block px-4 py-3 border border-gray-900 text-sm font-semibold">
                        Edit customer pack text
                    </a>

                    <div class="border border-gray-300 bg-gray-50 p-4 text-sm text-gray-700">
                        <p class="font-semibold">Coming next</p>
                        <p class="mt-1">
                            Compile customer pack, generate PDF and send quote will be added after this foundation is working.
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Add site survey note</h2>

                <form method="POST" action="{{ route('admin.quotes.notes.store', $quote) }}" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="note_type" class="block text-sm font-semibold mb-2">
                                Type
                            </label>

                            <select id="note_type"
                                    name="type"
                                    class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                    required>
                                <option value="general">General</option>
                                <option value="measurement">Measurement</option>
                                <option value="requirement">Requirement</option>
                                <option value="consideration">Consideration</option>
                                <option value="risk">Risk</option>
                                <option value="material">Material</option>
                                <option value="equipment">Equipment</option>
                                <option value="customer_comment">Customer comment</option>
                                <option value="internal">Internal</option>
                            </select>
                        </div>

                        <div>
                            <label for="room_or_area" class="block text-sm font-semibold mb-2">
                                Room / area
                            </label>

                            <input id="room_or_area"
                                   name="room_or_area"
                                   type="text"
                                   class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                   placeholder="e.g. Kitchen, rear garden, footings">
                        </div>
                    </div>

                    <div>
                        <label for="body" class="block text-sm font-semibold mb-2">
                            Note
                        </label>

                        <textarea id="body"
                                  name="body"
                                  rows="5"
                                  class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                  placeholder="Add the site visit note..."
                                  required></textarea>
                    </div>

                    <button type="submit"
                            class="px-5 py-3 bg-black text-white text-sm font-semibold">
                        Add note
                    </button>
                </form>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Add line item</h2>

                <form method="POST" action="{{ route('admin.quotes.line-items.store', $quote) }}" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="line_type" class="block text-sm font-semibold mb-2">
                                Type
                            </label>

                            <input id="line_type"
                                   name="type"
                                   type="text"
                                   value="works"
                                   class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                   required>
                        </div>

                        <div>
                            <label for="unit" class="block text-sm font-semibold mb-2">
                                Unit
                            </label>

                            <input id="unit"
                                   name="unit"
                                   type="text"
                                   value="item"
                                   class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                   required>
                        </div>

                        <div>
                            <label for="quantity" class="block text-sm font-semibold mb-2">
                                Quantity
                            </label>

                            <input id="quantity"
                                   name="quantity"
                                   type="number"
                                   min="0.01"
                                   step="0.01"
                                   value="1"
                                   class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                   required>
                        </div>

                        <div>
                            <label for="unit_amount" class="block text-sm font-semibold mb-2">
                                Unit amount
                            </label>

                            <input id="unit_amount"
                                   name="unit_amount"
                                   type="number"
                                   min="0"
                                   step="0.01"
                                   value="0.00"
                                   class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                   required>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-semibold mb-2">
                            Description
                        </label>

                        <input id="description"
                               name="description"
                               type="text"
                               class="w-full border border-gray-400 px-4 py-3 rounded-none"
                               placeholder="e.g. Skimming and plastering works"
                               required>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_optional" value="1">
                        Optional item
                    </label>

                    <button type="submit"
                            class="px-5 py-3 bg-black text-white text-sm font-semibold">
                        Add line item
                    </button>
                </form>
            </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Site survey notes</h2>

                <div class="space-y-4">
                    @forelse ($quote->notes as $note)
                        <article class="border border-gray-300 bg-gray-50 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        {{ ucfirst(str_replace('_', ' ', $note->type)) }}
                                        @if ($note->room_or_area)
                                            — {{ $note->room_or_area }}
                                        @endif
                                    </div>

                                    <p class="text-sm whitespace-pre-line mt-2">{{ $note->body }}</p>

                                    <p class="text-xs text-gray-500 mt-3">
                                        {{ $note->creator?->name ?: 'Unknown user' }}
                                        ·
                                        {{ $note->created_at ? $note->created_at->format('d M Y H:i') : '' }}
                                    </p>
                                </div>

                                <form method="POST" action="{{ route('admin.quotes.notes.destroy', [$quote, $note]) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="text-xs underline text-red-700"
                                            onclick="return confirm('Delete this note?')">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-gray-600">
                            No survey notes have been added yet.
                        </p>
                    @endforelse
                </div>
            </section>

            <section class="border border-gray-300 bg-white p-6">
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
                                        <div class="font-semibold">
                                            {{ $lineItem->description }}
                                        </div>

                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ ucfirst($lineItem->type) }}
                                            @if ($lineItem->is_optional)
                                                · Optional
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-3 py-3">
                                        {{ $lineItem->quantity }}
                                    </td>

                                    <td class="px-3 py-3">
                                        £{{ $lineItem->unit_amount }} / {{ $lineItem->unit }}
                                    </td>

                                    <td class="px-3 py-3 font-semibold">
                                        £{{ $lineItem->total }}
                                    </td>

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
                                <th colspan="3" class="px-3 py-3 text-right">
                                    Total
                                </th>
                                <th class="px-3 py-3 text-left">
                                    £{{ $quote->total }}
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Customer pack text</h2>

                <div class="space-y-5 text-sm">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Customer message</h3>
                        <p class="whitespace-pre-line">{{ $quote->final_customer_message ?: 'Not added yet.' }}</p>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Scope of works</h3>
                        <p class="whitespace-pre-line">{{ $quote->final_scope ?: 'Not added yet.' }}</p>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Estimated timeline</h3>
                        <p class="whitespace-pre-line">{{ $quote->final_timeline ?: 'Not added yet.' }}</p>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Assumptions</h3>
                        <p class="whitespace-pre-line">{{ $quote->final_assumptions ?: 'Not added yet.' }}</p>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Exclusions</h3>
                        <p class="whitespace-pre-line">{{ $quote->final_exclusions ?: 'Not added yet.' }}</p>
                    </div>
                </div>
            </section>

            <section class="border border-gray-300 bg-white p-6">
                <h2 class="text-lg font-semibold mb-4">Follow-ups</h2>

                <form method="POST" action="{{ route('admin.quotes.follow-ups.store', $quote) }}" class="space-y-4 mb-6">
                    @csrf

                    <div>
                        <label for="due_at" class="block text-sm font-semibold mb-2">
                            Follow-up due
                        </label>

                        <input id="due_at"
                               name="due_at"
                               type="datetime-local"
                               class="w-full border border-gray-400 px-4 py-3 rounded-none"
                               required>
                    </div>

                    <div>
                        <label for="follow_up_note" class="block text-sm font-semibold mb-2">
                            Note
                        </label>

                        <textarea id="follow_up_note"
                                  name="note"
                                  rows="3"
                                  class="w-full border border-gray-400 px-4 py-3 rounded-none"
                                  placeholder="e.g. Call customer to check whether they want to proceed"></textarea>
                    </div>

                    <button type="submit"
                            class="px-5 py-3 bg-black text-white text-sm font-semibold">
                        Add follow-up
                    </button>
                </form>

                <div class="space-y-3">
                    @forelse ($quote->followUps as $followUp)
                        <div class="border border-gray-300 bg-gray-50 p-4 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold">
                                        {{ $followUp->due_at ? $followUp->due_at->format('d M Y H:i') : '—' }}
                                    </p>

                                    @if ($followUp->note)
                                        <p class="mt-2 whitespace-pre-line">{{ $followUp->note }}</p>
                                    @endif

                                    <p class="text-xs text-gray-500 mt-2">
                                        Assigned to: {{ $followUp->assignedUser?->name ?: '—' }}
                                    </p>

                                    @if ($followUp->completed_at)
                                        <p class="text-xs text-green-800 font-semibold mt-2">
                                            Completed {{ $followUp->completed_at->format('d M Y H:i') }}
                                        </p>
                                    @endif
                                </div>

                                @if (! $followUp->completed_at)
                                    <form method="POST" action="{{ route('admin.quotes.follow-ups.complete', [$quote, $followUp]) }}">
                                        @csrf

                                        <button type="submit" class="text-xs underline">
                                            Mark complete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-600">
                            No follow-up reminders have been added yet.
                        </p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>