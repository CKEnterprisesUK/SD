<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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

    /**
     * Whether this (contractor) user is assigned to at least one project.
     *
     * Used to conditionally surface the contractor "My Projects" nav link and
     * dashboard tile. Returns false for non-contractors or contractor users
     * without a linked Contractor record.
     */
    public function hasAssignedProjects(): bool
    {
        if (! $this->isContractor()) {
            return false;
        }

        $contractor = $this->contractor;

        if ($contractor === null) {
            return false;
        }

        return Project::whereHas(
            'contractors',
            fn ($query) => $query->whereKey($contractor->id)
        )->exists();
    }
}
