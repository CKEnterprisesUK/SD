<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerActivityLog;

/**
 * Records customer audit events (created / updated / invited) with the acting
 * user, for display on the customer dashboard activity feed.
 */
class CustomerActivityLogger
{
    public static function created(Customer $customer): CustomerActivityLog
    {
        return self::log($customer, 'created', 'Customer record created.');
    }

    /**
     * @param  array<string, array{0: mixed, 1: mixed}>  $changes  field => [old, new]
     */
    public static function updated(Customer $customer, array $changes = []): CustomerActivityLog
    {
        $old = [];
        $new = [];

        foreach ($changes as $field => [$before, $after]) {
            $old[$field] = $before;
            $new[$field] = $after;
        }

        $description = count($changes)
            ? 'Updated: ' . implode(', ', array_map(fn ($f) => self::label($f), array_keys($changes))) . '.'
            : 'Customer record updated.';

        return self::log($customer, 'updated', $description, $old ?: null, $new ?: null);
    }

    public static function invited(Customer $customer, string $email): CustomerActivityLog
    {
        return self::log($customer, 'invited', 'Sent portal invite to ' . $email . '.');
    }

    public static function loggedIn(Customer $customer, ?int $actingUserId = null): CustomerActivityLog
    {
        return self::log($customer, 'logged_in', 'Signed in to the portal.', null, null, $actingUserId);
    }

    public static function loggedOut(Customer $customer, ?int $actingUserId = null): CustomerActivityLog
    {
        return self::log($customer, 'logged_out', 'Signed out of the portal.', null, null, $actingUserId);
    }

    public static function passwordReset(Customer $customer, ?int $actingUserId = null): CustomerActivityLog
    {
        return self::log($customer, 'password_reset', 'Reset their portal password.', null, null, $actingUserId);
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public static function log(
        Customer $customer,
        string $action,
        ?string $description = null,
        ?array $old = null,
        ?array $new = null,
        ?int $actingUserId = null,
    ): CustomerActivityLog {
        return CustomerActivityLog::create([
            'customer_id' => $customer->id,
            'user_id' => $actingUserId ?? auth()->id(),
            'action' => $action,
            'description' => $description,
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }

    private static function label(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }
}
