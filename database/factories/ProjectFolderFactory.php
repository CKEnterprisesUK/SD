<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectFolder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectFolder>
 */
class ProjectFolderFactory extends Factory
{
    protected $model = ProjectFolder::class;

    /**
     * Define the model's default state (a top-level folder).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'parent_id' => null,
            'name' => fake()->words(2, true),
            'is_top_level' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * Indicate that the folder is a top-level folder.
     */
    public function topLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
            'is_top_level' => true,
        ]);
    }

    /**
     * Indicate that the folder is a child of the given (or a new) top-level folder.
     */
    public function child(?ProjectFolder $parent = null): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            $parent ??= ProjectFolder::factory()->topLevel()->create();

            return [
                'project_id' => $parent->project_id,
                'parent_id' => $parent->id,
                'is_top_level' => false,
            ];
        });
    }
}
