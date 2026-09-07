<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Customer has no factory yet (added by a sibling task); create a real
            // linked customer inline so the project is always associated with one.
            'customer_id' => fn () => Customer::create([
                'name' => fake()->company(),
                'status' => 'active',
            ])->id,
            'created_by_user_id' => null,
            'name' => fake()->words(3, true),
            'reference' => fake()->optional()->bothify('PRJ-####'),
            'state' => fake()->randomElement(Project::STATES),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
