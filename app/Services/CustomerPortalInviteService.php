<?php

namespace App\Services;

use App\Mail\CustomerPortalInvite;
use App\Models\Customer;
use App\Models\CustomerInvitation;
use App\Models\PortalSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Provisions a customer portal account and sends the branded invitation email.
 *
 * Used by both the create-time invite (CustomerController@store) and the
 * dashboard "Send invite" button (CustomerInviteController@send) so the two
 * paths behave identically: find/create a `customer`-role User linked to the
 * customer, record a CustomerInvitation, flag the contact's portal access,
 * send the branded password-setup email, and record an activity log entry.
 */
class CustomerPortalInviteService
{
    /**
     * @return bool True when the branded email was dispatched.
     */
    public function invite(
        Customer $customer,
        string $email,
        string $name,
        ?int $customerContactId = null,
    ): bool {
        $user = User::where('email', $email)->first();

        if (! $user) {
            // `role` and `status` are not mass-assignable on User, so set them
            // explicitly to guarantee an active customer-role account.
            $user = new User([
                'name' => $name,
                'email' => $email,
                'customer_id' => $customer->id,
            ]);
            $user->password = Hash::make(Str::random(40));
            $user->role = 'customer';
            $user->status = 'active';
            $user->save();
        }

        CustomerInvitation::create([
            'customer_id' => $customer->id,
            'customer_contact_id' => $customerContactId,
            'email' => $user->email,
            'invited_by_user_id' => auth()->id(),
            'user_id' => $user->id,
        ]);

        if ($customerContactId) {
            $customer->contacts()->whereKey($customerContactId)->update([
                'portal_access_enabled' => true,
            ]);
        }

        $settings = PortalSetting::current();

        // Build the password-setup link the same way SiteDeskResetPasswordNotification
        // does, but deliver it inside our own branded mailable.
        $token = Password::broker()->createToken($user);

        $setupUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], false));

        Mail::to($user->email)->send(new CustomerPortalInvite(
            customer: $customer,
            recipientName: $name,
            setupUrl: $setupUrl,
            settings: $settings,
        ));

        CustomerActivityLogger::invited($customer, $user->email);

        return true;
    }
}
