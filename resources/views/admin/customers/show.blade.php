<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $customer->display_name }}
                </h2>

                <p class="text-sm text-gray-600 mt-1">
                    Customer record
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.customers.index') }}"
                   class="inline-flex px-5 py-3 border border-black text-sm font-semibold rounded-none">
                    Back to customers
                </a>

                <a href="{{ route('admin.customers.edit', $customer) }}"
                   class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Edit customer
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $currentQuotes = $currentQuotes ?? collect();
        $recentQuotes = $recentQuotes ?? collect();
        $projects = $projects ?? collect();

        $statusClass = match ($customer->status) {
            'active' => 'border-green-700 bg-green-50 text-green-900',
            'prospect' => 'border-yellow-700 bg-yellow-50 text-yellow-900',
            'inactive' => 'border-gray-500 bg-gray-50 text-gray-800',
            'archived' => 'border-gray-400 bg-white text-gray-600',
            default => 'border-gray-400 bg-white text-gray-700',
        };

        $quoteStatusClass = function (?string $status) {
            return match ($status) {
                'draft' => 'border-gray-500 bg-gray-50 text-gray-800',
                'survey_in_progress' => 'border-blue-700 bg-blue-50 text-blue-900',
                'survey_completed', 'ai_compiled' => 'border-yellow-700 bg-yellow-50 text-yellow-900',
                'sent' => 'border-purple-700 bg-purple-50 text-purple-900',
                'accepted' => 'border-green-700 bg-green-50 text-green-900',
                'declined', 'cancelled', 'expired' => 'border-red-700 bg-red-50 text-red-900',
                default => 'border-gray-400 bg-white text-gray-700',
            };
        };

        $primaryContact = $customer->primaryContact ?? $customer->contacts->firstWhere('is_primary', true);
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
                                {{ ucfirst($customer->status) }}
                            </span>
                        </div>

                        <h1 class="text-3xl font-bold text-gray-900">
                            {{ $customer->display_name }}
                        </h1>

                        <p class="text-gray-600 mt-2">
                            Customer record, contacts and linked quotes.
                        </p>

                        @if ($customer->address)
                            <div class="mt-5">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Customer / site address
                                </p>

                                <p class="text-sm text-gray-800 whitespace-pre-line mt-1">
                                    {{ $customer->address }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3 w-full lg:w-[420px]">
                        <div class="border border-gray-300 bg-gray-50 p-4">
                            <div class="text-xs font-semibold text-gray-600">
                                Current quotes
                            </div>

                            <div class="text-2xl font-bold mt-1">
                                {{ $currentQuotes->count() }}
                            </div>
                        </div>

                        <div class="border border-gray-300 bg-gray-50 p-4">
                            <div class="text-xs font-semibold text-gray-600">
                                Contacts
                            </div>

                            <div class="text-2xl font-bold mt-1">
                                {{ $customer->contacts->count() }}
                            </div>
                        </div>

                        <div class="border border-gray-300 bg-gray-50 p-4 col-span-2">
                            <div class="text-xs font-semibold text-gray-600">
                                Primary contact
                            </div>

                            <div class="text-sm font-semibold mt-1">
                                {{ $primaryContact?->name ?: '—' }}
                            </div>

                            @if ($primaryContact?->email)
                                <div class="text-xs text-gray-600 mt-1">
                                    {{ $primaryContact->email }}
                                </div>
                            @endif

                            @if ($primaryContact?->phone)
                                <div class="text-xs text-gray-600 mt-1">
                                    {{ $primaryContact->phone }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if (Route::has('admin.quotes.create'))
                        <a href="{{ route('admin.quotes.create', ['customer_id' => $customer->id]) }}"
                           class="block border border-black bg-black text-white p-5">
                            <div class="font-bold text-lg">
                                Create quote
                            </div>

                            <p class="text-sm mt-2 text-white/90">
                                Start a new quote for this customer.
                            </p>
                        </a>
                    @endif

                    <a href="{{ route('admin.customers.edit', $customer) }}"
                       class="block border border-gray-300 bg-white p-5 hover:border-black">
                        <div class="font-bold text-lg">
                            Edit customer
                        </div>

                        <p class="text-sm text-gray-600 mt-2">
                            Update customer details, status, notes and address.
                        </p>
                    </a>

                    @if (Route::has('admin.projects.create'))
                        <a href="{{ route('admin.projects.create', ['customer_id' => $customer->id]) }}"
                           class="block border border-gray-300 bg-white p-5 hover:border-black">
                            <div class="font-bold text-lg">
                                Create project
                            </div>

                            <p class="text-sm text-gray-600 mt-2">
                                Start a new project for this customer.
                            </p>
                        </a>
                    @endif

                    @if ($currentQuotes->count() && Route::has('admin.quotes.index'))
                        <a href="{{ route('admin.quotes.index') }}"
                           class="block border border-gray-300 bg-white p-5 hover:border-black">
                            <div class="font-bold text-lg">
                                View all quotes
                            </div>

                            <p class="text-sm text-gray-600 mt-2">
                                Open the quote list to review all customer quotes.
                            </p>
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <section class="border border-gray-300 bg-white">
            <div class="p-6 border-b border-gray-300 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        Current quotes
                    </h2>

                    <p class="text-sm text-gray-600 mt-1">
                        Open quotes for this customer. Accepted, declined and cancelled quotes are excluded.
                    </p>
                </div>

                @if (Route::has('admin.quotes.create'))
                    <a href="{{ route('admin.quotes.create', ['customer_id' => $customer->id]) }}"
                       class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                        Create quote
                    </a>
                @endif
            </div>

            <div class="p-6">
                @if ($currentQuotes->count())
                    <div class="overflow-x-auto border border-gray-300">
                        <table class="min-w-full divide-y divide-gray-300 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                        Quote
                                    </th>

                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                        Title
                                    </th>

                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                        Status
                                    </th>

                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                        Total
                                    </th>

                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                        Valid until
                                    </th>

                                    <th class="px-4 py-3 text-right font-semibold text-gray-700">
                                        Action
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($currentQuotes as $quote)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold">
                                            {{ $quote->quote_number }}
                                        </td>

                                        <td class="px-4 py-3">
                                            {{ $quote->title ?: 'Untitled quote' }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 border text-xs font-semibold {{ $quoteStatusClass($quote->status) }}">
                                                {{ ucfirst(str_replace('_', ' ', $quote->status)) }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            £{{ $quote->total }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $quote->valid_until ? $quote->valid_until->format('d M Y') : '—' }}
                                        </td>

                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            @if (Route::has('admin.quotes.show'))
                                                <a href="{{ route('admin.quotes.show', $quote) }}"
                                                   class="underline font-semibold">
                                                    Open
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="border border-gray-300 bg-gray-50 p-5">
                        <p class="text-sm text-gray-700">
                            No current quotes for this customer.
                        </p>
                    </div>
                @endif
            </div>
        </section>

        <section class="border border-gray-300 bg-white">
            <div class="p-6 border-b border-gray-300 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        Projects
                    </h2>

                    <p class="text-sm text-gray-600 mt-1">
                        Projects linked to this customer record.
                    </p>
                </div>

                @if (Route::has('admin.projects.create'))
                    <a href="{{ route('admin.projects.create', ['customer_id' => $customer->id]) }}"
                       class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                        Create project
                    </a>
                @endif
            </div>

            <div class="p-6">
                @if ($projects->count())
                    <div class="overflow-x-auto border border-gray-300">
                        <table class="min-w-full divide-y divide-gray-300 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                        Project
                                    </th>

                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                        State
                                    </th>

                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                        Created
                                    </th>

                                    <th class="px-4 py-3 text-right font-semibold text-gray-700">
                                        Action
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($projects as $project)
                                    <tr>
                                        <td class="px-4 py-3 font-semibold">
                                            {{ $project->name }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 border border-gray-500 bg-gray-50 text-xs font-semibold">
                                                {{ $project->state }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $project->created_at ? $project->created_at->format('d M Y') : '—' }}
                                        </td>

                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            @if (Route::has('admin.projects.show'))
                                                <a href="{{ route('admin.projects.show', $project) }}"
                                                   class="underline font-semibold">
                                                    Open
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="border border-gray-300 bg-gray-50 p-5">
                        <p class="text-sm text-gray-700">
                            No projects for this customer.
                        </p>
                    </div>
                @endif
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 border border-gray-300 bg-white">
                <div class="p-6 border-b border-gray-300">
                    <h2 class="text-lg font-bold text-gray-900">
                        Customer details
                    </h2>
                </div>

                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                        <div>
                            <dt class="font-semibold text-gray-700">
                                Customer name
                            </dt>

                            <dd class="mt-1">
                                {{ $customer->name }}
                            </dd>
                        </div>

                        <div>
                            <dt class="font-semibold text-gray-700">
                                Company name
                            </dt>

                            <dd class="mt-1">
                                {{ $customer->company_name ?: '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="font-semibold text-gray-700">
                                Status
                            </dt>

                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 border text-xs font-semibold {{ $statusClass }}">
                                    {{ ucfirst($customer->status) }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="font-semibold text-gray-700">
                                Created
                            </dt>

                            <dd class="mt-1">
                                {{ $customer->created_at ? $customer->created_at->format('d M Y H:i') : '—' }}
                            </dd>
                        </div>
                    </dl>

                    @if ($customer->address)
                        <div class="mt-6 border-t border-gray-300 pt-6">
                            <h3 class="font-semibold text-gray-700 text-sm mb-2">
                                Customer / site address
                            </h3>

                            <p class="text-sm whitespace-pre-line">
                                {{ $customer->address }}
                            </p>
                        </div>
                    @endif

                    @if ($customer->notes)
                        <div class="mt-6 border-t border-gray-300 pt-6">
                            <h3 class="font-semibold text-gray-700 text-sm mb-2">
                                Notes
                            </h3>

                            <p class="text-sm whitespace-pre-line">
                                {{ $customer->notes }}
                            </p>
                        </div>
                    @endif
                </div>
            </section>

            <aside class="space-y-6">
                <section class="border border-gray-300 bg-white">
                    <div class="p-6 border-b border-gray-300">
                        <h2 class="text-lg font-bold text-gray-900">
                            Quote summary
                        </h2>
                    </div>

                    <div class="p-6 space-y-4 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-gray-600">
                                Current quotes
                            </span>

                            <span class="font-semibold">
                                {{ $currentQuotes->count() }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <span class="text-gray-600">
                                Recent closed quotes
                            </span>

                            <span class="font-semibold">
                                {{ $recentQuotes->count() }}
                            </span>
                        </div>

                        @if ($currentQuotes->count())
                            <div class="border-t border-gray-300 pt-4">
                                <p class="text-xs text-gray-600">
                                    This customer already has open quote activity. Check the current quotes before creating another quote.
                                </p>
                            </div>
                        @endif
                    </div>
                </section>

                @if ($recentQuotes->count())
                    <section class="border border-gray-300 bg-white">
                        <div class="p-6 border-b border-gray-300">
                            <h2 class="text-lg font-bold text-gray-900">
                                Recent closed quotes
                            </h2>
                        </div>

                        <div class="divide-y divide-gray-200">
                            @foreach ($recentQuotes as $quote)
                                <div class="p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-semibold text-sm">
                                                {{ $quote->quote_number }}
                                            </p>

                                            <p class="text-sm text-gray-600 mt-1">
                                                {{ $quote->title ?: 'Untitled quote' }}
                                            </p>

                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ ucfirst(str_replace('_', ' ', $quote->status)) }} · £{{ $quote->total }}
                                            </p>
                                        </div>

                                        @if (Route::has('admin.quotes.show'))
                                            <a href="{{ route('admin.quotes.show', $quote) }}"
                                               class="text-sm underline font-semibold">
                                                Open
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </aside>
        </div>

        <section class="border border-gray-300 bg-white">
            <div class="p-6 border-b border-gray-300 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        Contacts
                    </h2>

                    <p class="text-sm text-gray-600 mt-1">
                        People linked to this customer record.
                    </p>
                </div>
            </div>

            <div class="p-6">
                <div class="overflow-x-auto border border-gray-300">
                    <table class="min-w-full divide-y divide-gray-300 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                    Contact
                                </th>

                                <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                    Email
                                </th>

                                <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                    Phone
                                </th>

                                <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                    Role
                                </th>

                                <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                    Flags
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($customer->contacts as $contact)
                                <tr>
                                    <td class="px-4 py-3 font-semibold">
                                        {{ $contact->name ?: '—' }}

                                        @if ($contact->is_primary)
                                            <span class="ml-2 inline-flex px-2 py-1 border border-black text-xs font-semibold">
                                                Primary
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        @if ($contact->email)
                                            <a href="mailto:{{ $contact->email }}" class="underline">
                                                {{ $contact->email }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $contact->phone ?: '—' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $contact->role ?: '—' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            @if ($contact->receives_quotes)
                                                <span class="inline-flex px-2 py-1 border border-gray-500 bg-gray-50 text-xs">
                                                    Quotes
                                                </span>
                                            @endif

                                            @if ($contact->receives_invoices)
                                                <span class="inline-flex px-2 py-1 border border-gray-500 bg-gray-50 text-xs">
                                                    Invoices
                                                </span>
                                            @endif

                                            @if ($contact->portal_access_enabled)
                                                <span class="inline-flex px-2 py-1 border border-green-700 bg-green-50 text-green-900 text-xs">
                                                    Portal
                                                </span>
                                            @endif

                                            @if (! $contact->receives_quotes && ! $contact->receives_invoices && ! $contact->portal_access_enabled)
                                                <span class="text-gray-500">
                                                    —
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-600">
                                        No contacts have been added yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>