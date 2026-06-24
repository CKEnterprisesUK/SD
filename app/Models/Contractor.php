<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contractor extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'company_name',
        'day_rate_pence',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDayRateAttribute(): string
    {
        return number_format($this->day_rate_pence / 100, 2);
    }
}