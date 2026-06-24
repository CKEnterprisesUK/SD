<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorInvoice extends Model
{
    protected $fillable = [
        'contractor_id',
        'user_id',
        'invoice_number',
        'invoice_date',
        'week_commencing',
        'supplier_name',
        'supplier_email',
        'supplier_phone',
        'supplier_address',
        'customer_name',
        'customer_address',
        'default_day_rate_pence',
        'actual_day_rate_pence',
        'day_rate_overridden',
        'worked_monday',
        'worked_tuesday',
        'worked_wednesday',
        'worked_thursday',
        'worked_friday',
        'worked_saturday',
        'worked_sunday',
        'days_worked',
        'subtotal_pence',
        'vat_pence',
        'total_pence',
        'contractor_notes',
        'contractor_confirmation_text',
        'submitted_at',
        'submitted_ip',
        'status',
        'pdf_path',
        'emailed_at',
        'paid_at',
        'cancelled_at',
        'monday_days',
        'tuesday_days',
        'wednesday_days',
        'thursday_days',
        'friday_days',
        'saturday_days',
        'sunday_days',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'week_commencing' => 'date',
        'day_rate_overridden' => 'boolean',
        'worked_monday' => 'boolean',
        'worked_tuesday' => 'boolean',
        'worked_wednesday' => 'boolean',
        'worked_thursday' => 'boolean',
        'worked_friday' => 'boolean',
        'worked_saturday' => 'boolean',
        'worked_sunday' => 'boolean',
        'days_worked' => 'decimal:1',
        'submitted_at' => 'datetime',
        'emailed_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'monday_days' => 'decimal:2',
        'tuesday_days' => 'decimal:2',
        'wednesday_days' => 'decimal:2',
        'thursday_days' => 'decimal:2',
        'friday_days' => 'decimal:2',
        'saturday_days' => 'decimal:2',
        'sunday_days' => 'decimal:2',
    ];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDefaultDayRateAttribute(): string
    {
        return number_format($this->default_day_rate_pence / 100, 2);
    }

    public function getActualDayRateAttribute(): string
    {
        return number_format($this->actual_day_rate_pence / 100, 2);
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