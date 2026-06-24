<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    protected $fillable = [
        'customer_id',
        'created_by_user_id',
        'assigned_user_id',
        'quote_number',
        'title',
        'status',
        'site_address',
        'summary',
        'internal_notes',
        'final_customer_message',
        'final_scope',
        'final_assumptions',
        'final_exclusions',
        'final_timeline',
        'final_terms',
        'subtotal_pence',
        'vat_pence',
        'total_pence',
        'valid_until',
        'sent_at',
        'accepted_at',
        'declined_at',
        'expired_at',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(QuoteNote::class)->latest();
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(QuoteLineItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(QuoteFollowUp::class)->orderBy('due_at');
    }

    public function files(): HasMany
    {
        return $this->hasMany(QuoteFile::class)->latest();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(QuoteFile::class)->where('type', 'photo')->latest();
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(QuoteAiGeneration::class)->latest();
    }

    public function aiDrafts(): HasMany
    {
        return $this->hasMany(QuoteAiDraft::class)->latest();
    }

    public function latestAiDraft(): HasMany
    {
        return $this->aiDrafts()->limit(1);
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

    public function recalculateTotals(): void
    {
        $subtotal = (int) $this->lineItems()
            ->where('is_optional', false)
            ->sum('total_pence');

        $rateCard = PricingRateCard::activeOrCreateDefault();
        $vat = (int) round($subtotal * ((float) $rateCard->vat_percent / 100));

        $this->update([
            'subtotal_pence' => $subtotal,
            'vat_pence' => $vat,
            'total_pence' => $subtotal + $vat,
        ]);
    }
}
