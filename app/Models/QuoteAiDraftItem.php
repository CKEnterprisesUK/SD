<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteAiDraftItem extends Model
{
    protected $fillable = [
        'quote_ai_draft_id',
        'quote_id',
        'pricing_rate_item_id',
        'category',
        'rate_item_code',
        'clean_customer_description',
        'internal_reasoning',
        'quantity',
        'unit',
        'base_unit_cost_pence',
        'base_total_pence',
        'markup_percent',
        'contingency_percent',
        'vat_percent',
        'contingency_pence',
        'markup_pence',
        'subtotal_pence',
        'vat_pence',
        'total_pence',
        'confidence',
        'pricing_source',
        'evidence',
        'warnings',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'contingency_percent' => 'decimal:2',
        'vat_percent' => 'decimal:2',
        'evidence' => 'array',
        'warnings' => 'array',
    ];

    public function draft(): BelongsTo
    {
        return $this->belongsTo(QuoteAiDraft::class, 'quote_ai_draft_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function rateItem(): BelongsTo
    {
        return $this->belongsTo(PricingRateItem::class, 'pricing_rate_item_id');
    }

    public function getBaseUnitCostAttribute(): string
    {
        return number_format($this->base_unit_cost_pence / 100, 2);
    }

    public function getBaseTotalAttribute(): string
    {
        return number_format($this->base_total_pence / 100, 2);
    }

    public function getSubtotalAttribute(): string
    {
        return number_format($this->subtotal_pence / 100, 2);
    }

    public function getVatAttribute(): string
    {
        return number_format($this->vat_pence / 100, 2);
    }

    public function getTotalAttribute(): string
    {
        return number_format($this->total_pence / 100, 2);
    }
}
