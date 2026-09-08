<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CustomerInviteController extends Controller
{
    /**
     * Invite a customer contact to the portal.
     *
     * Mirrors ContractorController@sendInvite: create/find a `customer`-role
     * User linked to the customer via `customer_id`, record a
     * CustomerInvitation, and dispatch a password reset link (which delivers
     * the SiteDeskResetPasswordNotification for password setup).
     */
    public function send(Request $request, Customer $customer)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'customer_contact_id' => ['nullable', 'exists:customer_contacts,id'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            // `role` and `status` are not in the User model's mass-assignable
            // Fillable set, so they are assigned explicitly to guarantee the
            // invited user is persisted as a `customer` (Requirement 3.3).
            $user = new User([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'customer_id' => $customer->id,
            ]);
            $user->password = Hash::make(Str::random(40));
            $user->role = 'customer';
            $user->status = 'active';
            $user->save();
        }

        CustomerInvitation::create([
            'customer_id' => $customer->id,
            'customer_contact_id' => $validated['customer_contact_id'] ?? null,
            'email' => $user->email,
            'invited_by_user_id' => auth()->id(),
            'user_id' => $user->id,
        ]);

        // Mark the invited contact as having portal access so the dashboard
        // reflects the invite state (Portal badge / "Resend invite").
        if (! empty($validated['customer_contact_id'])) {
            $customer->contacts()->whereKey($validated['customer_contact_id'])->update([
                'portal_access_enabled' => true,
            ]);
        }

        Password::sendResetLink([
            'email' => $user->email,
        ]);

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', 'Customer invite/password setup email sent.');
    }
}
