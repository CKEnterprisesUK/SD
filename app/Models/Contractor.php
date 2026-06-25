<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contractor extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
         'address',
        'company_name',
        'day_rate_pence',
        'status',
    ];

    public function invoices(): HasMany
{
    return $this->hasMany(ContractorInvoice::class);
}

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ContractorActivityLog::class);
    }

    public function getDayRateAttribute(): string
    {
        return number_format($this->day_rate_pence / 100, 2);
    }
}