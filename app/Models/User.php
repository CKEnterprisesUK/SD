<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Notifications\SiteDeskResetPasswordNotification;

#[Fillable(['name', 'email', 'password', 'customer_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function contractor(): HasOne
{
    return $this->hasOne(Contractor::class);
}
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function isAdmin(): bool
{
    return $this->role === 'admin';
}

public function contractorInvoices(): HasMany
{
    return $this->hasMany(ContractorInvoice::class);
}

public function isContractor(): bool
{
    return $this->role === 'contractor';
}

public function customer(): BelongsTo
{
    return $this->belongsTo(Customer::class);
}

public function isCustomer(): bool
{
    return $this->role === 'customer';
}
}
