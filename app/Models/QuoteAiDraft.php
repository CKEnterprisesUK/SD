<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteAiDraft extends Model
{
    protected $fillable = [
        'quote_id',
        'quote_ai_generation_id',
        'status',
        'detected_job_type',
        'selected_template_code',
        'overall_confidence',
        'pricing_basis',
        'missing_information',
        'warnings',
        'assumptions',
        'exclusions',
        'internal_reasoning',
        'customer_message',
        'scope_of_works',
        'timeline',
        'terms',
    ];

    protected $casts = [
        'missing_information' => 'array',
        'warnings' => 'array',
        'assumptions' => 'array',
        'exclusions' => 'array',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(QuoteAiGeneration::class, 'quote_ai_generation_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteAiDraftItem::class)->orderBy('id');
    }

    public function pendingItems(): HasMany
    {
        return $this->items()->where('status', 'pending');
    }

    public function acceptedItems(): HasMany
    {
        return $this->items()->where('status', 'accepted');
    }

    public function getItemsSubtotalAttribute(): string
    {
        return number_format($this->items->sum('subtotal_pence') / 100, 2);
    }

    public function getItemsVatAttribute(): string
    {
        return number_format($this->items->sum('vat_pence') / 100, 2);
    }

    public function getItemsTotalAttribute(): string
    {
        return number_format($this->items->sum('total_pence') / 100, 2);
    }
}
