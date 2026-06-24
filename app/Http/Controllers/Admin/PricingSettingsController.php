<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingJobTemplate;
use App\Models\PricingRateCard;
use App\Models\PricingRateItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PricingSettingsController extends Controller
{
    public function edit()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $rateCard = PricingRateCard::activeOrCreateDefault()->load('items');
        $templates = PricingJobTemplate::orderBy('category')->orderBy('name')->get();

        return view('admin.settings.pricing', [
            'rateCard' => $rateCard,
            'rateItems' => $rateCard->items,
            'templates' => $templates,
            'categories' => PricingRateItem::CATEGORIES,
        ]);
    }

    public function updateRateCard(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $rateCard = PricingRateCard::activeOrCreateDefault();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'default_markup_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'high_risk_markup_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'contingency_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'vat_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'regional_adjustment_percent' => ['required', 'numeric', 'min:-50', 'max:100'],
            'preliminaries_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'minimum_job_charge' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'block_quote_sending_if_high_risk_missing_info' => ['nullable', 'boolean'],
        ]);

        $validated['minimum_job_charge_pence'] = (int) round($validated['minimum_job_charge'] * 100);
        $validated['block_quote_sending_if_high_risk_missing_info'] = $request->boolean('block_quote_sending_if_high_risk_missing_info');
        unset($validated['minimum_job_charge']);

        $rateCard->update($validated);

        return redirect()->route('admin.pricing-settings.edit')->with('status', 'Pricing defaults updated.');
    }

    public function storeRateItem(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $rateCard = PricingRateCard::activeOrCreateDefault();
        $validated = $this->validateRateItem($request);
        $validated['pricing_rate_card_id'] = $rateCard->id;
        $validated['base_cost_pence'] = (int) round($validated['base_cost'] * 100);
        $validated['is_active'] = $request->boolean('is_active', true);
        unset($validated['base_cost']);

        PricingRateItem::create($validated);

        return redirect()->route('admin.pricing-settings.edit')->with('status', 'Rate-card item added.');
    }

    public function updateRateItem(Request $request, PricingRateItem $rateItem)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $this->validateRateItem($request, $rateItem);
        $validated['base_cost_pence'] = (int) round($validated['base_cost'] * 100);
        $validated['is_active'] = $request->boolean('is_active');
        unset($validated['base_cost']);

        $rateItem->update($validated);

        return redirect()->route('admin.pricing-settings.edit')->with('status', 'Rate-card item updated.');
    }

    public function destroyRateItem(PricingRateItem $rateItem)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $rateItem->delete();

        return redirect()->route('admin.pricing-settings.edit')->with('status', 'Rate-card item deleted.');
    }

    public function storeTemplate(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $this->validateTemplate($request);
        $validated['is_active'] = $request->boolean('is_active', true);

        PricingJobTemplate::create($validated);

        return redirect()->route('admin.pricing-settings.edit')->with('status', 'Job template added.');
    }

    public function updateTemplate(Request $request, PricingJobTemplate $template)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $this->validateTemplate($request, $template);
        $validated['is_active'] = $request->boolean('is_active');

        $template->update($validated);

        return redirect()->route('admin.pricing-settings.edit')->with('status', 'Job template updated.');
    }

    public function destroyTemplate(PricingJobTemplate $template)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $template->delete();

        return redirect()->route('admin.pricing-settings.edit')->with('status', 'Job template deleted.');
    }

    private function validateRateItem(Request $request, ?PricingRateItem $rateItem = null): array
    {
        return $request->validate([
            'category' => ['required', Rule::in(PricingRateItem::CATEGORIES)],
            'code' => ['required', 'string', 'max:100', Rule::unique('pricing_rate_items', 'code')->ignore($rateItem)],
            'name' => ['required', 'string', 'max:255'],
            'customer_description' => ['nullable', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'base_cost' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'default_markup_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'aliases' => ['nullable', 'string'],
            'quantity_rules' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function validateTemplate(Request $request, ?PricingJobTemplate $template = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('pricing_job_templates', 'code')->ignore($template)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'typical_scope' => ['nullable', 'string'],
            'typical_labour' => ['nullable', 'string'],
            'typical_materials' => ['nullable', 'string'],
            'typical_plant' => ['nullable', 'string'],
            'typical_waste' => ['nullable', 'string'],
            'default_assumptions' => ['nullable', 'string'],
            'default_exclusions' => ['nullable', 'string'],
            'risk_notes' => ['nullable', 'string'],
            'required_information' => ['nullable', 'string'],
            'suggested_rate_item_codes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
