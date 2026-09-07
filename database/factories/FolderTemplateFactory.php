<?php

namespace Database\Factories;

use App\Models\FolderTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FolderTemplate>
 */
class FolderTemplateFactory extends Factory
{
    protected $model = FolderTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $levels = ['read-write', 'read-only', 'no-access'];

        return [
            'name' => fake()->unique()->words(2, true),
            'sort_order' => fake()->numberBetween(0, 10),
            'subfolders' => fake()->words(fake()->numberBetween(1, 3)),
            'permissions' => [
                'admin' => 'read-write',
                'contractor' => fake()->randomElement($levels),
                'customer' => fake()->randomElement($levels),
            ],
        ];
    }
}
