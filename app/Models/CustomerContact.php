<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerContact extends Model
{
    protected $fillable = [
        'customer_id',
        'name',
        'email',
        'phone',
        'role',
        'is_primary',
        'receives_quotes',
        'receives_invoices',
        'portal_access_enabled',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'receives_quotes' => 'boolean',
        'receives_invoices' => 'boolean',
        'portal_access_enabled' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}