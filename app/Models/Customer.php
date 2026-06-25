<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    protected $fillable = [
        'created_by_user_id',
        'name',
        'company_name',
        'status',
        'address',
        'notes',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->orderBy('id');
    }

    public function quotes(): HasMany
{
    return $this->hasMany(Quote::class);
}

    public function primaryContact(): HasOne
    {
        return $this->hasOne(CustomerContact::class)
            ->where('is_primary', true);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
    }
}