<?php

namespace Database\Factories;

use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectContractor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectContractor>
 */
class ProjectContractorFactory extends Factory
{
    protected $model = ProjectContractor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            // Contractor has no factory yet (added by a sibling task); create a
            // real contractor inline so the assignment is always associated.
            'contractor_id' => fn () => Contractor::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'status' => 'active',
            ])->id,
            'assigned_by_user_id' => null,
        ];
    }
}
