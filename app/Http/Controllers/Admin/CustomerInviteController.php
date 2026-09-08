<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerPortalInviteService;
use Illuminate\Http\Request;

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
    public function send(Request $request, Customer $customer, CustomerPortalInviteService $inviter)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'customer_contact_id' => ['nullable', 'exists:customer_contacts,id'],
        ]);

        $inviter->invite(
            $customer,
            $validated['email'],
            $validated['name'],
            $validated['customer_contact_id'] ?? null,
        );

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', 'Green Street Portal invite sent.');
    }
}
