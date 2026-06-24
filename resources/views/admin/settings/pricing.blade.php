<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">AI & Pricing Settings</h2>
            <a href="{{ route('admin.settings.edit') }}" class="inline-flex px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">Portal settings</a>
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
            <h1 class="text-2xl font-bold">Controlled pricing</h1>
            <p class="text-gray-600 mt-2 max-w-3xl">
                AI selects rate-card items and quantities. SiteDesk calculates contingency, markup and VAT from your settings.
            </p>
        </section>

        <section class="border border-gray-300 bg-white p-6">
            <h2 class="text-lg font-semibold mb-4">Pricing defaults</h2>
            <form method="POST" action="{{ route('admin.pricing-settings.rate-card.update') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                @method('PUT')

                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold mb-2">Rate card name</label>
                    <input name="name" type="text" value="{{ old('name', $rateCard->name) }}" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                </div>

                <div><label class="block text-sm font-semibold mb-2">Default markup %</label><input name="default_markup_percent" type="number" step="0.01" min="0" max="100" value="{{ old('default_markup_percent', $rateCard->default_markup_percent) }}" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                <div><label class="block text-sm font-semibold mb-2">High-risk markup %</label><input name="high_risk_markup_percent" type="number" step="0.01" min="0" max="100" value="{{ old('high_risk_markup_percent', $rateCard->high_risk_markup_percent) }}" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                <div><label class="block text-sm font-semibold mb-2">Contingency %</label><input name="contingency_percent" type="number" step="0.01" min="0" max="100" value="{{ old('contingency_percent', $rateCard->contingency_percent) }}" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                <div><label class="block text-sm font-semibold mb-2">VAT %</label><input name="vat_percent" type="number" step="0.01" min="0" max="100" value="{{ old('vat_percent', $rateCard->vat_percent) }}" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                <div><label class="block text-sm font-semibold mb-2">Regional adjustment %</label><input name="regional_adjustment_percent" type="number" step="0.01" min="-50" max="100" value="{{ old('regional_adjustment_percent', $rateCard->regional_adjustment_percent) }}" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                <div><label class="block text-sm font-semibold mb-2">Preliminaries %</label><input name="preliminaries_percent" type="number" step="0.01" min="0" max="100" value="{{ old('preliminaries_percent', $rateCard->preliminaries_percent) }}" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                <div><label class="block text-sm font-semibold mb-2">Minimum job charge</label><input name="minimum_job_charge" type="number" step="0.01" min="0" value="{{ old('minimum_job_charge', $rateCard->minimum_job_charge) }}" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>

                <label class="md:col-span-2 inline-flex items-center gap-2 text-sm mt-8">
                    <input type="checkbox" name="block_quote_sending_if_high_risk_missing_info" value="1" @checked(old('block_quote_sending_if_high_risk_missing_info', $rateCard->block_quote_sending_if_high_risk_missing_info))>
                    Block quote sending if high-risk missing information exists
                </label>

                <div class="md:col-span-3">
                    <button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">Save pricing defaults</button>
                </div>
            </form>
        </section>

        <section class="border border-gray-300 bg-white p-6">
            <h2 class="text-lg font-semibold mb-4">Add rate-card item</h2>
            <form method="POST" action="{{ route('admin.pricing-settings.rate-items.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold mb-2">Category</label>
                    <select name="category" class="w-full border border-gray-400 px-4 py-3 rounded-none" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}">{{ str_replace('_', ' ', ucfirst($category)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="block text-sm font-semibold mb-2">Code</label><input name="code" type="text" placeholder="labour_day_skilled" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                <div><label class="block text-sm font-semibold mb-2">Name</label><input name="name" type="text" placeholder="Skilled labour" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                <div><label class="block text-sm font-semibold mb-2">Unit</label><input name="unit" type="text" value="day" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                <div><label class="block text-sm font-semibold mb-2">Base cost</label><input name="base_cost" type="number" step="0.01" min="0" value="0.00" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                <div><label class="block text-sm font-semibold mb-2">Item markup %, optional</label><input name="default_markup_percent" type="number" step="0.01" min="0" max="100" class="w-full border border-gray-400 px-4 py-3 rounded-none"></div>
                <div><label class="block text-sm font-semibold mb-2">Item VAT %, optional</label><input name="vat_percent" type="number" step="0.01" min="0" max="100" class="w-full border border-gray-400 px-4 py-3 rounded-none"></div>
                <label class="inline-flex items-center gap-2 text-sm mt-8"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                <div class="md:col-span-2"><label class="block text-sm font-semibold mb-2">Customer description</label><input name="customer_description" type="text" placeholder="Skilled labour for agreed works" class="w-full border border-gray-400 px-4 py-3 rounded-none"></div>
                <div class="md:col-span-2"><label class="block text-sm font-semibold mb-2">Aliases</label><input name="aliases" type="text" placeholder="plumber, carpenter, tiler, roofer" class="w-full border border-gray-400 px-4 py-3 rounded-none"></div>
                <div class="md:col-span-2"><label class="block text-sm font-semibold mb-2">Quantity rules</label><textarea name="quantity_rules" rows="3" class="w-full border border-gray-400 px-4 py-3 rounded-none"></textarea></div>
                <div class="md:col-span-2"><label class="block text-sm font-semibold mb-2">Internal notes for AI</label><textarea name="internal_notes" rows="3" class="w-full border border-gray-400 px-4 py-3 rounded-none"></textarea></div>
                <div class="md:col-span-4"><button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">Add rate item</button></div>
            </form>
        </section>

        <section class="border border-gray-300 bg-white p-6">
            <h2 class="text-lg font-semibold mb-4">Current rate card</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-300 bg-gray-50 text-left">
                            <th class="px-3 py-2">Code</th><th class="px-3 py-2">Name</th><th class="px-3 py-2">Category</th><th class="px-3 py-2">Unit</th><th class="px-3 py-2">Base cost</th><th class="px-3 py-2">Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rateItems as $item)
                            <tr class="border-b border-gray-200">
                                <td class="px-3 py-2 font-mono text-xs">{{ $item->code }}</td>
                                <td class="px-3 py-2">{{ $item->name }}</td>
                                <td class="px-3 py-2">{{ str_replace('_', ' ', $item->category) }}</td>
                                <td class="px-3 py-2">{{ $item->unit }}</td>
                                <td class="px-3 py-2">£{{ $item->base_cost }}</td>
                                <td class="px-3 py-2">{{ $item->is_active ? 'Yes' : 'No' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-8 text-center text-gray-600">No rate-card items yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="border border-gray-300 bg-white p-6">
            <h2 class="text-lg font-semibold mb-4">Add job template</h2>
            <form method="POST" action="{{ route('admin.pricing-settings.templates.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div><label class="block text-sm font-semibold mb-2">Code</label><input name="code" type="text" placeholder="bathroom_refurbishment" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                <div><label class="block text-sm font-semibold mb-2">Name</label><input name="name" type="text" placeholder="Bathroom refurbishment" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                <div><label class="block text-sm font-semibold mb-2">Category</label><input name="category" type="text" placeholder="bathroom" class="w-full border border-gray-400 px-4 py-3 rounded-none" required></div>
                <textarea name="description" rows="3" class="md:col-span-3 border border-gray-400 px-4 py-3 rounded-none" placeholder="Template description"></textarea>
                <textarea name="typical_scope" rows="3" class="border border-gray-400 px-4 py-3 rounded-none" placeholder="Typical scope"></textarea>
                <textarea name="typical_labour" rows="3" class="border border-gray-400 px-4 py-3 rounded-none" placeholder="Typical labour"></textarea>
                <textarea name="typical_materials" rows="3" class="border border-gray-400 px-4 py-3 rounded-none" placeholder="Typical materials"></textarea>
                <textarea name="typical_waste" rows="3" class="border border-gray-400 px-4 py-3 rounded-none" placeholder="Typical waste"></textarea>
                <textarea name="required_information" rows="3" class="border border-gray-400 px-4 py-3 rounded-none" placeholder="Required information"></textarea>
                <textarea name="default_exclusions" rows="3" class="border border-gray-400 px-4 py-3 rounded-none" placeholder="Default exclusions"></textarea>
                <textarea name="risk_notes" rows="3" class="border border-gray-400 px-4 py-3 rounded-none" placeholder="Risks"></textarea>
                <textarea name="suggested_rate_item_codes" rows="3" class="border border-gray-400 px-4 py-3 rounded-none" placeholder="Suggested rate item codes"></textarea>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                <div class="md:col-span-3"><button type="submit" class="px-5 py-3 bg-black text-white text-sm font-semibold rounded-none">Add template</button></div>
            </form>
        </section>

        <section class="border border-gray-300 bg-white p-6">
            <h2 class="text-lg font-semibold mb-4">Job templates</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse ($templates as $template)
                    <article class="border border-gray-200 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold">{{ $template->name }}</h3>
                                <p class="text-xs text-gray-500 font-mono mt-1">{{ $template->code }}</p>
                                <p class="text-sm text-gray-600 mt-2">{{ $template->description }}</p>
                            </div>
                            <span class="text-xs border border-gray-300 px-2 py-1">{{ $template->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                        @if ($template->required_information)
                            <p class="text-sm mt-3"><span class="font-semibold">Required:</span> {{ $template->required_information }}</p>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-gray-600">No templates yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
