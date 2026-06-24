<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRateItem extends Model
{
    public const CATEGORIES = [
        'labour',
        'materials',
        'plant_equipment',
        'waste_disposal',
        'preliminaries',
        'provisional_sum',
        'structure',
        'roofing',
        'finishes',
        'joinery',
        'other_works',
    ];

    public const GUIDANCE_TYPES = [
        'measured_rate',
        'typical_item',
        'project_allowance',
        'provisional_sum',
        'historical_example',
        'risk_allowance',
        'other',
    ];

    protected $fillable = [
        'pricing_rate_card_id',
        'category',
        'guidance_type',
        'code',
        'name',
        'customer_description',
        'unit',
        'base_cost_pence',
        'default_markup_percent',
        'vat_percent',
        'pricing_basis',
        'low_estimate_pence',
        'typical_estimate_pence',
        'high_estimate_pence',
        'aliases',
        'context',
        'assumptions',
        'exclusions',
        'risk_notes',
        'quantity_rules',
        'internal_notes',
        'is_active',
    ];

    protected $casts = [
        'default_markup_percent' => 'decimal:2',
        'vat_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function rateCard(): BelongsTo
    {
        return $this->belongsTo(PricingRateCard::class, 'pricing_rate_card_id');
    }

    public function getBaseCostAttribute(): string
    {
        return number_format(($this->base_cost_pence ?? 0) / 100, 2);
    }

    public function getLowEstimateAttribute(): string
    {
        return number_format(($this->low_estimate_pence ?? 0) / 100, 2);
    }

    public function getTypicalEstimateAttribute(): string
    {
        return number_format(($this->typical_estimate_pence ?? $this->base_cost_pence ?? 0) / 100, 2);
    }

    public function getHighEstimateAttribute(): string
    {
        return number_format(($this->high_estimate_pence ?? 0) / 100, 2);
    }

    public function toAiContext(): array
    {
        return [
            'code' => $this->code,
            'category' => $this->category,
            'guidance_type' => $this->guidance_type,
            'name' => $this->name,
            'customer_description' => $this->customer_description,
            'unit' => $this->unit,
            'pricing_basis' => $this->pricing_basis ?: 'pricing_guidance',
            'low_estimate_pounds' => $this->low_estimate_pence !== null ? round($this->low_estimate_pence / 100, 2) : null,
            'typical_estimate_pounds' => $this->typical_estimate_pence !== null
                ? round($this->typical_estimate_pence / 100, 2)
                : round(($this->base_cost_pence ?? 0) / 100, 2),
            'high_estimate_pounds' => $this->high_estimate_pence !== null ? round($this->high_estimate_pence / 100, 2) : null,
            'aliases' => $this->aliases,
            'context' => $this->context,
            'assumptions' => $this->assumptions,
            'exclusions' => $this->exclusions,
            'risk_notes' => $this->risk_notes,
            'quantity_rules' => $this->quantity_rules,
            'internal_notes' => $this->internal_notes,
        ];
    }
}