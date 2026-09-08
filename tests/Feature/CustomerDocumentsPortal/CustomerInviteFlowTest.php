<?php

namespace Tests\Feature\CustomerDocumentsPortal;

use App\Mail\CustomerPortalInvite;
use App\Models\Customer;
use App\Models\CustomerInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Feature: customer-documents-portal, Property 6: customer invite flow
 *
 * Property 6: Invite creates a pending customer invitation; on acceptance the
 * resulting User has role `customer` linked to the Customer (assert a branded
 * portal invitation email is sent via Mail).
 *
 * The invite endpoint (admin.customers.invite) delegates to
 * CustomerPortalInviteService: it creates/finds a `customer`-role User linked to
 * the Customer via `customer_id`, records a pending CustomerInvitation
 * (accepted_at null), and dispatches a branded App\Mail\CustomerPortalInvite
 * mailable that carries the password-setup link for portal access.
 *
 * Acceptance is exercised by completing the actual password-reset route with a
 * real token; after acceptance the customer-role User can authenticate and
 * remains linked to the Customer via customer_id.
 *
 * Validates: Requirements 3.2, 3.3
 */
class CustomerInviteFlowTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 15;

    /**
     * Property 6: invite creates a pending invitation + linked customer-role user
     * and sends the branded portal invitation email. Runs several randomized
     * iterations.
     */
    public function test_invite_creates_pending_invitation_and_sends_branded_email(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            Mail::fake();

            $admin = User::factory()->create(['role' => 'admin']);
            $customer = Customer::create([
                'name' => fake()->company(),
                'status' => 'active',
            ]);

            $name = fake()->name();
            $email = fake()->unique()->safeEmail();

            $response = $this->actingAs($admin)->post(
                route('admin.customers.invite', $customer),
                ['name' => $name, 'email' => $email]
            );

            $response->assertRedirect(route('admin.customers.show', $customer));

            // A customer-role User exists, linked to the Customer via customer_id (3.3).
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user, sprintf('Iteration %d: invited user must exist.', $i));
            $this->assertSame('customer', $user->role, sprintf('Iteration %d: role must be customer.', $i));
            $this->assertSame(
                $customer->id,
                $user->customer_id,
                sprintf('Iteration %d: user must be linked to the customer.', $i)
            );

            // A pending CustomerInvitation row was created (3.2).
            $invitation = CustomerInvitation::where('customer_id', $customer->id)
                ->where('email', $email)
                ->first();
            $this->assertNotNull($invitation, sprintf('Iteration %d: invitation must exist.', $i));
            $this->assertNull(
                $invitation->accepted_at,
                sprintf('Iteration %d: invitation must be pending (accepted_at null).', $i)
            );
            $this->assertSame(
                $customer->id,
                $invitation->customer_id,
                sprintf('Iteration %d: invitation linked to the customer.', $i)
            );
            $this->assertSame($user->id, $invitation->user_id);

            // The branded portal invitation email was sent to the invited address.
            Mail::assertSent(
                CustomerPortalInvite::class,
                fn ($mail) => $mail->hasTo($email)
            );
        }
    }

    /**
     * Acceptance: completing the password-reset flow with a real token sets a
     * password; the customer-role User can then authenticate and stays linked to
     * the Customer. (Requirements 3.2, 3.3)
     */
    public function test_invited_customer_can_accept_and_authenticate(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create([
            'name' => fake()->company(),
            'status' => 'active',
        ]);

        $email = fake()->unique()->safeEmail();

        $this->actingAs($admin)
            ->post(route('admin.customers.invite', $customer), [
                'name' => fake()->name(),
                'email' => $email,
            ])
            ->assertRedirect(route('admin.customers.show', $customer));

        $user = User::where('email', $email)->firstOrFail();

        // The invite left the admin authenticated in the test session. The
        // password-reset routes live behind the `guest` middleware, so the reset
        // POST must be made as an unauthenticated visitor (mirroring the invited
        // customer following the emailed link). Otherwise `guest` redirects the
        // still-authenticated admin away and the controller never runs.
        auth()->logout();

        // Generate a real reset token for the user (the invite emailed one).
        $token = Password::broker()->createToken($user);

        $newPassword = 'new-secure-password-123';

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        // A successful reset redirects to login with no validation errors; a
        // failed reset redirects back with an `email` error. Assert success.
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('login'));

        $user->refresh();

        // Linkage preserved after acceptance (3.3).
        $this->assertSame('customer', $user->role);
        $this->assertSame($customer->id, $user->customer_id);

        // The password was actually persisted (hash matches the chosen password).
        $this->assertTrue(
            Hash::check($newPassword, $user->password),
            'The reset flow should have persisted the new password hash.'
        );

        // The user can authenticate with the newly set password.
        $this->assertTrue(
            auth()->attempt(['email' => $email, 'password' => $newPassword]),
            'Invited customer should be able to log in after setting a password.'
        );
    }
}
