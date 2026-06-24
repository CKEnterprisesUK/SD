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
        'other_works',
    ];

    protected $fillable = [
        'pricing_rate_card_id',
        'category',
        'code',
        'name',
        'customer_description',
        'unit',
        'base_cost_pence',
        'default_markup_percent',
        'vat_percent',
        'aliases',
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
        return number_format($this->base_cost_pence / 100, 2);
    }

    public function toAiContext(): array
    {
        return [
            'code' => $this->code,
            'category' => $this->category,
            'name' => $this->name,
            'customer_description' => $this->customer_description,
            'unit' => $this->unit,
            'base_cost_pounds' => round($this->base_cost_pence / 100, 2),
            'aliases' => $this->aliases,
            'quantity_rules' => $this->quantity_rules,
            'internal_notes' => $this->internal_notes,
        ];
    }
}
