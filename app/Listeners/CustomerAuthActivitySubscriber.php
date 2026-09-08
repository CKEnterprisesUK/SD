<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\CustomerActivityLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;

/**
 * Records portal sign-in, sign-out and password-reset events for customer-role
 * users into the customer activity feed. Only customer users are logged; admin
 * and contractor authentication is ignored here.
 */
class CustomerAuthActivitySubscriber
{
    public function handleLogin(Login $event): void
    {
        $this->logForCustomer($event->user, fn ($customer, $userId) => CustomerActivityLogger::loggedIn($customer, $userId));
    }

    public function handleLogout(Logout $event): void
    {
        // On logout the guard may pass null (e.g. already-invalidated session).
        if ($event->user === null) {
            return;
        }

        $this->logForCustomer($event->user, fn ($customer, $userId) => CustomerActivityLogger::loggedOut($customer, $userId));
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->logForCustomer($event->user, fn ($customer, $userId) => CustomerActivityLogger::passwordReset($customer, $userId));
    }

    /**
     * Resolve the customer for a customer-role user and run the given logger.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $authUser
     */
    private function logForCustomer($authUser, callable $log): void
    {
        if (! $authUser instanceof User || ! $authUser->isCustomer()) {
            return;
        }

        $customer = $authUser->customer;

        if ($customer === null) {
            return;
        }

        $log($customer, $authUser->id);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }
}
