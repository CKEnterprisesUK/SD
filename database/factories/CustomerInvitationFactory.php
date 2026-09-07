<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerInvitation>
 */
class CustomerInvitationFactory extends Factory
{
    protected $model = CustomerInvitation::class;

    /**
     * Define the model's default state (a pending invitation).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Customer has no factory yet (added by a sibling task); create a real
            // linked customer inline so the invitation is always associated with one.
            'customer_id' => fn () => Customer::create([
                'name' => fake()->company(),
                'status' => 'active',
            ])->id,
            'customer_contact_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'invited_by_user_id' => null,
            'user_id' => null,
            'accepted_at' => null,
        ];
    }

    /**
     * Indicate that the invitation has been accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => now(),
        ]);
    }
}
