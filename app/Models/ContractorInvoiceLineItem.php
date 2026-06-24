<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorInvoiceLineItem extends Model
{
    protected $fillable = [
        'contractor_invoice_id',
        'type',
        'description',
        'quantity',
        'unit_amount_pence',
        'total_pence',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ContractorInvoice::class, 'contractor_invoice_id');
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