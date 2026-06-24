<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'created_by_user_id',
        'name',
        'company_name',
        'status',
        'phone',
        'email',
        'address',
        'notes',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class)
            ->orderByDesc('is_primary')
            ->orderBy('name');
    }

    public function primaryContact()
    {
        return $this->hasOne(CustomerContact::class)
            ->where('is_primary', true);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
    }
}