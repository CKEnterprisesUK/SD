<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLineItem extends Model
{
    protected $fillable = [
        'quote_id',
        'source',
        'type',
        'description',
        'quantity',
        'unit',
        'unit_amount_pence',
        'total_pence',
        'is_optional',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'is_optional' => 'boolean',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function getUnitAmountAttribute(): string
    {
        return number_format($this->unit_amount_pence / 100, 2);
    }

    public function getTotalAttribute(): string
    {
        return number_format($this->total_pence / 100, 2);
    }
}