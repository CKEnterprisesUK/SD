@php
    $categoryLabels = [
        'labour' => 'Labour',
        'materials' => 'Materials',
        'plant_equipment' => 'Plant & equipment',
        'waste_disposal' => 'Waste disposal',
        'preliminaries' => 'Preliminaries',
        'provisional_sum' => 'Provisional sums',
        'other_works' => 'Other works',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    AI & Pricing Settings
                </h2>

                <p class="text-sm text-gray-600 mt-1">
                    Control how SiteDesk estimates labour, materials, waste, plant, markup, VAT and risk.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.quotes.index') }}"
                   class="inline-flex px-5 py-3 border border-black text-sm font-semibold rounded-none">
                    Quotes
                </a>

                <a href="{{ route('admin.settings.edit') }}"
                   class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                    Portal settings
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
                <p class="font-semibold mb-2">There is a problem with the form.</p>

                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Explanation -->
        <section class="border border-gray-300 bg-white p-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-gray-600">
                How this works
            </p>

            <h1 class="text-3xl font-bold mt-2">
                SiteDesk uses AI to understand the job, but your rate card controls the price.
            </h1>

            <p class="text-gray-700 mt-3 max-w-4xl">
                The assistant reads the quote, survey notes, photos, job templates and pricing hints.
                It then suggests which rate-card items apply and what quantities may be needed.
                SiteDesk calculates markup, contingency and VAT from the settings below.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                <div class="border border-gray-300 p-4">
                    <div class="text-sm font-bold">1. Set pricing rules</div>
                    <p class="text-sm text-gray-600 mt-1">
                        Markup, contingency, VAT, minimum job charge and regional adjustment.
                    </p>
                </div>

                <div class="border border-gray-300 p-4">
                    <div class="text-sm font-bold">2. Add rate-card items</div>
                    <p class="text-sm text-gray-600 mt-1">
                        Labour days, materials, skips, plant hire and provisional allowances.
                    </p>
                </div>

                <div class="border border-gray-300 p-4">
                    <div class="text-sm font-bold">3. Add job templates</div>
                    <p class="text-sm text-gray-600 mt-1">
                        Bathrooms, extensions, roofing, plastering, kitchens and common risks.
                    </p>
                </div>

                <div class="border border-gray-300 p-4">
                    <div class="text-sm font-bold">4. Review AI drafts</div>
                    <p class="text-sm text-gray-600 mt-1">
                        AI suggestions stay as drafts until you accept and apply them.
                    </p>
                </div>
            </div>

            <div class="border border-blue-700 bg-blue-50 p-4 mt-6">
                <h2 class="font-semibold text-blue-900">
                    Simple rule
                </h2>

                <p class="text-sm text-blue-900 mt-1">
                    AI can suggest <strong>what work is needed</strong> and <strong>how much may be needed</strong>.
                    SiteDesk uses your rate card to calculate <strong>what it costs</strong>.
                </p>
            </div>
        </section>

        <!-- Pricing defaults -->
        <section class="border border-gray-300 bg-white p-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6 mb-6">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gray-600">
                        Step 1
                    </p>

                    <h2 class="text-2xl font-bold mt-1">
                        Pricing policy
                    </h2>

                    <p class="text-sm text-gray-600 mt-2 max-w-3xl">
                        These settings apply across the estimate unless a specific rate-card item has its own markup or VAT setting.
                    </p>
                </div>

                <div class="border border-gray-300 bg-gray-50 p-4 text-sm max-w-md">
                    <p class="font-semibold">Example calculation</p>
                    <p class="text-gray-700 mt-1">
                        Base cost × contingency × markup + VAT = customer quote value.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.pricing-settings.rate-card.update') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                @method('PUT')

                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold mb-2">
                        Rate card name
                    </label>

                    <input
                        name="name"
                        type="text"
                        value="{{ old('name', $rateCard->name) }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >

                    <p class="text-xs text-gray-600 mt-1">
                        Example: Default rate card, London rate card, Commercial works rate card.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">
                        Default markup %
                    </label>

                    <input
                        name="default_markup_percent"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        value="{{ old('default_markup_percent', $rateCard->default_markup_percent) }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >

                    <p class="text-xs text-gray-600 mt-1">
                        Normal profit/overhead margin. Typical: 25–30%.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">
                        High-risk markup %
                    </label>

                    <input
                        name="high_risk_markup_percent"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        value="{{ old('high_risk_markup_percent', $rateCard->high_risk_markup_percent) }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >

                    <p class="text-xs text-gray-600 mt-1">
                        Used when AI confidence is low or site risk is higher.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">
                        Contingency %
                    </label>

                    <input
                        name="contingency_percent"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        value="{{ old('contingency_percent', $rateCard->contingency_percent) }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >

                    <p class="text-xs text-gray-600 mt-1">
                        Allowance for unknowns before markup is applied.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">
                        VAT %
                    </label>

                    <input
                        name="vat_percent"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        value="{{ old('vat_percent', $rateCard->vat_percent) }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >

                    <p class="text-xs text-gray-600 mt-1">
                        Usually 20% in the UK unless a specific job has different VAT treatment.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">
                        Regional adjustment %
                    </label>

                    <input
                        name="regional_adjustment_percent"
                        type="number"
                        step="0.01"
                        min="-50"
                        max="100"
                        value="{{ old('regional_adjustment_percent', $rateCard->regional_adjustment_percent) }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >

                    <p class="text-xs text-gray-600 mt-1">
                        Increase or reduce costs for your working region. Use 0 if not needed.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">
                        Preliminaries %
                    </label>

                    <input
                        name="preliminaries_percent"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        value="{{ old('preliminaries_percent', $rateCard->preliminaries_percent) }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >

                    <p class="text-xs text-gray-600 mt-1">
                        Site setup, management, admin, supervision and general job running costs.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">
                        Minimum job charge
                    </label>

                    <input
                        name="minimum_job_charge"
                        type="number"
                        step="0.01"
                        min="0"
                        value="{{ old('minimum_job_charge', $rateCard->minimum_job_charge) }}"
                        class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        required
                    >

                    <p class="text-xs text-gray-600 mt-1">
                        The minimum quote value before VAT rules are applied.
                    </p>
                </div>

                <label class="md:col-span-3 inline-flex items-start gap-2 text-sm border border-gray-300 bg-gray-50 p-4">
                    <input
                        type="checkbox"
                        name="block_quote_sending_if_high_risk_missing_info"
                        value="1"
                        class="mt-1"
                        @checked(old('block_quote_sending_if_high_risk_missing_info', $rateCard->block_quote_sending_if_high_risk_missing_info))
                    >

                    <span>
                        <span class="font-semibold block">Warn before sending if high-risk information is missing</span>
                        <span class="text-gray-600">
                            Recommended. This helps stop quotes being sent when measurements, access, specification or site risks are unclear.
                        </span>
                    </span>
                </label>

                <div class="md:col-span-3">
                    <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                        Save pricing policy
                    </button>
                </div>
            </form>
        </section>

        <!-- Rate card -->
        <section class="border border-gray-300 bg-white p-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6 mb-6">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gray-600">
                        Step 2
                    </p>

                    <h2 class="text-2xl font-bold mt-1">
                        Rate card
                    </h2>

                    <p class="text-sm text-gray-600 mt-2 max-w-3xl">
                        These are the cost items AI is allowed to choose from. Add labour day rates, material allowances,
                        skip costs, plant hire, preliminaries and provisional sums.
                    </p>
                </div>

                <div class="border border-gray-300 bg-gray-50 p-4 text-sm max-w-md">
                    <p class="font-semibold">Good rate-card item example</p>
                    <p class="text-gray-700 mt-1">
                        <span class="font-mono">labour_day_skilled</span> — Skilled tradesperson — day — £300.
                    </p>
                </div>
            </div>

            <details class="border border-gray-300 mb-6">
                <summary class="cursor-pointer px-4 py-3 font-semibold bg-gray-50">
                    Add a new rate-card item
                </summary>

                <form method="POST" action="{{ route('admin.pricing-settings.rate-items.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 border-t border-gray-300">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Category
                        </label>

                        <select name="category" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}">
                                    {{ $categoryLabels[$category] ?? ucwords(str_replace('_', ' ', $category)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Code
                        </label>

                        <input
                            name="code"
                            type="text"
                            placeholder="labour_day_skilled"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none font-mono text-sm"
                            required
                        >

                        <p class="text-xs text-gray-600 mt-1">
                            No spaces. AI uses this to identify the item.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Name
                        </label>

                        <input
                            name="name"
                            type="text"
                            placeholder="Skilled labour"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                            required
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Unit
                        </label>

                        <input
                            name="unit"
                            type="text"
                            value="day"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                            required
                        >

                        <p class="text-xs text-gray-600 mt-1">
                            Examples: day, hour, item, each, m2, linear metre.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Base cost
                        </label>

                        <input
                            name="base_cost"
                            type="number"
                            step="0.01"
                            min="0"
                            value="0.00"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                            required
                        >

                        <p class="text-xs text-gray-600 mt-1">
                            Your cost before markup, contingency and VAT.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Item markup %, optional
                        </label>

                        <input
                            name="default_markup_percent"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        >

                        <p class="text-xs text-gray-600 mt-1">
                            Leave blank to use the main markup.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Item VAT %, optional
                        </label>

                        <input
                            name="vat_percent"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        >

                        <p class="text-xs text-gray-600 mt-1">
                            Leave blank to use the main VAT rate.
                        </p>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm mt-8">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Active
                    </label>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold mb-2">
                            Customer description
                        </label>

                        <input
                            name="customer_description"
                            type="text"
                            placeholder="Skilled labour for agreed works"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        >

                        <p class="text-xs text-gray-600 mt-1">
                            Clean wording that can appear on the customer quote.
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold mb-2">
                            Search words / aliases
                        </label>

                        <input
                            name="aliases"
                            type="text"
                            placeholder="plumber, carpenter, tiler, roofer, skilled trade"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                        >

                        <p class="text-xs text-gray-600 mt-1">
                            Helps AI match messy survey notes to this rate-card item.
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold mb-2">
                            Quantity guidance for AI
                        </label>

                        <textarea
                            name="quantity_rules"
                            rows="4"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                            placeholder="Example: Small repair usually 0.5–1 day. Bathroom refurb usually 5–12 days depending on specification."
                        ></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold mb-2">
                            Internal notes for AI
                        </label>

                        <textarea
                            name="internal_notes"
                            rows="4"
                            class="w-full border border-gray-400 px-4 py-3 rounded-none"
                            placeholder="Example: Use this item for qualified trade work where exact trade split is unclear."
                        ></textarea>
                    </div>

                    <div class="md:col-span-4">
                        <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                            Add rate-card item
                        </button>
                    </div>
                </form>
            </details>

            <div class="space-y-4">
                @forelse ($rateItems as $item)
                    <article class="border border-gray-300">
                        <div class="p-4 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex border border-gray-300 px-2 py-1 text-xs font-semibold">
                                        {{ $categoryLabels[$item->category] ?? ucwords(str_replace('_', ' ', $item->category)) }}
                                    </span>

                                    <span class="inline-flex border px-2 py-1 text-xs font-semibold {{ $item->is_active ? 'border-green-700 text-green-800 bg-green-50' : 'border-gray-400 text-gray-700 bg-gray-50' }}">
                                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>

                                <h3 class="font-semibold text-lg mt-2">
                                    {{ $item->name }}
                                </h3>

                                <p class="font-mono text-xs text-gray-600 mt-1">
                                    {{ $item->code }}
                                </p>

                                @if ($item->customer_description)
                                    <p class="text-sm text-gray-700 mt-2">
                                        {{ $item->customer_description }}
                                    </p>
                                @endif
                            </div>

                            <div class="grid grid-cols-3 gap-3 text-sm min-w-80">
                                <div class="border border-gray-300 p-3">
                                    <div class="text-gray-600">Unit</div>
                                    <div class="font-bold">{{ $item->unit }}</div>
                                </div>

                                <div class="border border-gray-300 p-3">
                                    <div class="text-gray-600">Base cost</div>
                                    <div class="font-bold">£{{ $item->base_cost }}</div>
                                </div>

                                <div class="border border-gray-300 p-3">
                                    <div class="text-gray-600">Markup</div>
                                    <div class="font-bold">{{ $item->default_markup_percent ?: 'Default' }}</div>
                                </div>
                            </div>
                        </div>

                        <details class="border-t border-gray-300">
                            <summary class="cursor-pointer px-4 py-3 bg-gray-50 text-sm font-semibold">
                                Edit this rate-card item
                            </summary>

                            <div class="p-4 border-t border-gray-300">
                                <form method="POST" action="{{ route('admin.pricing-settings.rate-items.update', $item) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    @csrf
                                    @method('PUT')

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Category</label>

                                        <select name="category" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category }}" @selected($item->category === $category)>
                                                    {{ $categoryLabels[$category] ?? ucwords(str_replace('_', ' ', $category)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Code</label>
                                        <input name="code" type="text" value="{{ $item->code }}" class="w-full border border-gray-400 px-4 py-3 rounded-none font-mono text-sm" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Name</label>
                                        <input name="name" type="text" value="{{ $item->name }}" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Unit</label>
                                        <input name="unit" type="text" value="{{ $item->unit }}" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Base cost</label>
                                        <input name="base_cost" type="number" step="0.01" min="0" value="{{ $item->base_cost }}" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Item markup %, optional</label>
                                        <input name="default_markup_percent" type="number" step="0.01" min="0" max="100" value="{{ $item->default_markup_percent }}" class="w-full border border-gray-400 px-4 py-3 rounded-none">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Item VAT %, optional</label>
                                        <input name="vat_percent" type="number" step="0.01" min="0" max="100" value="{{ $item->vat_percent }}" class="w-full border border-gray-400 px-4 py-3 rounded-none">
                                    </div>

                                    <label class="inline-flex items-center gap-2 text-sm mt-8">
                                        <input type="checkbox" name="is_active" value="1" @checked($item->is_active)>
                                        Active
                                    </label>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold mb-2">Customer description</label>
                                        <input name="customer_description" type="text" value="{{ $item->customer_description }}" class="w-full border border-gray-400 px-4 py-3 rounded-none">
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold mb-2">Search words / aliases</label>
                                        <input name="aliases" type="text" value="{{ $item->aliases }}" class="w-full border border-gray-400 px-4 py-3 rounded-none">
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold mb-2">Quantity guidance for AI</label>
                                        <textarea name="quantity_rules" rows="4" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ $item->quantity_rules }}</textarea>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold mb-2">Internal notes for AI</label>
                                        <textarea name="internal_notes" rows="4" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ $item->internal_notes }}</textarea>
                                    </div>

                                    <div class="md:col-span-4">
                                        <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                                            Save rate-card item
                                        </button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.pricing-settings.rate-items.destroy', $item) }}" class="mt-4">
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="text-sm text-red-700 underline"
                                        onclick="return confirm('Delete this rate-card item? It is usually safer to mark it inactive instead.')"
                                    >
                                        Delete rate-card item
                                    </button>
                                </form>
                            </div>
                        </details>
                    </article>
                @empty
                    <div class="border border-gray-300 bg-gray-50 p-6 text-sm text-gray-700">
                        No rate-card items have been added yet. Add labour, materials, waste and plant items so the AI assistant has controlled pricing to work from.
                    </div>
                @endforelse
            </div>
        </section>

        <!-- Job templates -->
        <section class="border border-gray-300 bg-white p-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6 mb-6">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gray-600">
                        Step 3
                    </p>

                    <h2 class="text-2xl font-bold mt-1">
                        Job templates
                    </h2>

                    <p class="text-sm text-gray-600 mt-2 max-w-3xl">
                        Templates help AI understand common work types. They do not set the final price.
                        They describe typical labour, materials, waste, exclusions, risks and missing information.
                    </p>
                </div>

                <div class="border border-gray-300 bg-gray-50 p-4 text-sm max-w-md">
                    <p class="font-semibold">Good template example</p>
                    <p class="text-gray-700 mt-1">
                        Bathroom refurbishment: strip-out, plumbing, preparation, tiling, second fix, waste, access risks and specification questions.
                    </p>
                </div>
            </div>

            <details class="border border-gray-300 mb-6">
                <summary class="cursor-pointer px-4 py-3 font-semibold bg-gray-50">
                    Add a new job template
                </summary>

                <form method="POST" action="{{ route('admin.pricing-settings.templates.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 border-t border-gray-300">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold mb-2">Code</label>
                        <input name="code" type="text" placeholder="bathroom_refurbishment" class="w-full border border-gray-400 px-4 py-3 rounded-none font-mono text-sm" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Name</label>
                        <input name="name" type="text" placeholder="Bathroom refurbishment" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Category</label>
                        <input name="category" type="text" placeholder="bathrooms" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-sm font-semibold mb-2">Description</label>
                        <textarea name="description" rows="3" class="w-full border border-gray-400 px-4 py-3 rounded-none" placeholder="Short explanation of when this template should be used."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Typical scope</label>
                        <textarea name="typical_scope" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none" placeholder="Strip-out, preparation, installation, finishing..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Typical labour</label>
                        <textarea name="typical_labour" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none" placeholder="Skilled labour, general labour, specialist trades..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Typical materials</label>
                        <textarea name="typical_materials" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none" placeholder="Preparation materials, fixings, finish materials..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Typical plant</label>
                        <textarea name="typical_plant" rows="4" class="w-full border border-gray-400 px-4 py-3 rounded-none" placeholder="Access tower, mixer, small tools, plant hire..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Typical waste</label>
                        <textarea name="typical_waste" rows="4" class="w-full border border-gray-400 px-4 py-3 rounded-none" placeholder="Skip, waste bags, grab lorry, disposal route..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Suggested rate item codes</label>
                        <textarea name="suggested_rate_item_codes" rows="4" class="w-full border border-gray-400 px-4 py-3 rounded-none" placeholder="labour_day_skilled&#10;skip_6_yard&#10;prelim_site_setup"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Information AI should ask for</label>
                        <textarea name="required_information" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none" placeholder="Measurements, specification, access, material supply, risks..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Default exclusions</label>
                        <textarea name="default_exclusions" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none" placeholder="Hidden defects, asbestos, structural design, utility upgrades..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Risk notes</label>
                        <textarea name="risk_notes" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none" placeholder="Access issues, hidden conditions, drainage, damp, specification risk..."></textarea>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-sm font-semibold mb-2">Default assumptions</label>
                        <textarea name="default_assumptions" rows="4" class="w-full border border-gray-400 px-4 py-3 rounded-none" placeholder="Normal working hours, reasonable access, customer choices confirmed before ordering..."></textarea>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Active
                    </label>

                    <div class="md:col-span-3">
                        <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                            Add job template
                        </button>
                    </div>
                </form>
            </details>

            <div class="space-y-4">
                @forelse ($templates as $template)
                    <article class="border border-gray-300">
                        <div class="p-4 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex border border-gray-300 px-2 py-1 text-xs font-semibold">
                                        {{ $template->category }}
                                    </span>

                                    <span class="inline-flex border px-2 py-1 text-xs font-semibold {{ $template->is_active ? 'border-green-700 text-green-800 bg-green-50' : 'border-gray-400 text-gray-700 bg-gray-50' }}">
                                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>

                                <h3 class="font-semibold text-lg mt-2">
                                    {{ $template->name }}
                                </h3>

                                <p class="font-mono text-xs text-gray-600 mt-1">
                                    {{ $template->code }}
                                </p>

                                @if ($template->description)
                                    <p class="text-sm text-gray-700 mt-2">
                                        {{ $template->description }}
                                    </p>
                                @endif
                            </div>

                            <div class="border border-gray-300 bg-gray-50 p-4 text-sm lg:w-96">
                                <p class="font-semibold">AI uses this template to identify:</p>

                                <ul class="list-disc pl-5 mt-2 text-gray-700 space-y-1">
                                    <li>Typical labour and materials</li>
                                    <li>Likely waste and plant</li>
                                    <li>Missing information</li>
                                    <li>Assumptions, exclusions and risks</li>
                                </ul>
                            </div>
                        </div>

                        <details class="border-t border-gray-300">
                            <summary class="cursor-pointer px-4 py-3 bg-gray-50 text-sm font-semibold">
                                Edit this job template
                            </summary>

                            <div class="p-4 border-t border-gray-300">
                                <form method="POST" action="{{ route('admin.pricing-settings.templates.update', $template) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    @csrf
                                    @method('PUT')

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Code</label>
                                        <input name="code" type="text" value="{{ $template->code }}" class="w-full border border-gray-400 px-4 py-3 rounded-none font-mono text-sm" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Name</label>
                                        <input name="name" type="text" value="{{ $template->name }}" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Category</label>
                                        <input name="category" type="text" value="{{ $template->category }}" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                                    </div>

                                    <div class="md:col-span-3">
                                        <label class="block text-sm font-semibold mb-2">Description</label>
                                        <textarea name="description" rows="3" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ $template->description }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Typical scope</label>
                                        <textarea name="typical_scope" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ $template->typical_scope }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Typical labour</label>
                                        <textarea name="typical_labour" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ $template->typical_labour }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Typical materials</label>
                                        <textarea name="typical_materials" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ $template->typical_materials }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Typical plant</label>
                                        <textarea name="typical_plant" rows="4" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ $template->typical_plant }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Typical waste</label>
                                        <textarea name="typical_waste" rows="4" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ $template->typical_waste }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Suggested rate item codes</label>
                                        <textarea name="suggested_rate_item_codes" rows="4" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ $template->suggested_rate_item_codes }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Information AI should ask for</label>
                                        <textarea name="required_information" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ $template->required_information }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Default exclusions</label>
                                        <textarea name="default_exclusions" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ $template->default_exclusions }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold mb-2">Risk notes</label>
                                        <textarea name="risk_notes" rows="5" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ $template->risk_notes }}</textarea>
                                    </div>

                                    <div class="md:col-span-3">
                                        <label class="block text-sm font-semibold mb-2">Default assumptions</label>
                                        <textarea name="default_assumptions" rows="4" class="w-full border border-gray-400 px-4 py-3 rounded-none">{{ $template->default_assumptions }}</textarea>
                                    </div>

                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="is_active" value="1" @checked($template->is_active)>
                                        Active
                                    </label>

                                    <div class="md:col-span-3">
                                        <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">
                                            Save job template
                                        </button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.pricing-settings.templates.destroy', $template) }}" class="mt-4">
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="text-sm text-red-700 underline"
                                        onclick="return confirm('Delete this job template? It is usually safer to mark it inactive instead.')"
                                    >
                                        Delete job template
                                    </button>
                                </form>
                            </div>
                        </details>
                    </article>
                @empty
                    <div class="border border-gray-300 bg-gray-50 p-6 text-sm text-gray-700">
                        No job templates have been added yet. Add common work types so the AI assistant can understand jobs with less manual input.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>