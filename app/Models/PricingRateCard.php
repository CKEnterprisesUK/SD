<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingRateCard extends Model
{
    protected $fillable = [
        'name',
        'default_markup_percent',
        'high_risk_markup_percent',
        'contingency_percent',
        'vat_percent',
        'regional_adjustment_percent',
        'preliminaries_percent',
        'minimum_job_charge_pence',
        'block_quote_sending_if_high_risk_missing_info',
        'is_active',
    ];

    protected $casts = [
        'default_markup_percent' => 'decimal:2',
        'high_risk_markup_percent' => 'decimal:2',
        'contingency_percent' => 'decimal:2',
        'vat_percent' => 'decimal:2',
        'regional_adjustment_percent' => 'decimal:2',
        'preliminaries_percent' => 'decimal:2',
        'block_quote_sending_if_high_risk_missing_info' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PricingRateItem::class)->orderBy('category')->orderBy('name');
    }

    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true);
    }

    public static function activeOrCreateDefault(): self
    {
        $card = static::where('is_active', true)->latest('id')->first();

        if ($card) {
            return $card;
        }

        return static::create([
            'name' => 'Default rate card',
            'default_markup_percent' => 25,
            'high_risk_markup_percent' => 30,
            'contingency_percent' => 10,
            'vat_percent' => 20,
            'regional_adjustment_percent' => 0,
            'preliminaries_percent' => 5,
            'minimum_job_charge_pence' => 25000,
            'block_quote_sending_if_high_risk_missing_info' => true,
            'is_active' => true,
        ]);
    }

    public function getMinimumJobChargeAttribute(): string
    {
        return number_format($this->minimum_job_charge_pence / 100, 2);
    }
}
