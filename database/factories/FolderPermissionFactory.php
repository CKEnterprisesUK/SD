<?php

namespace Database\Factories;

use App\Models\FolderPermission;
use App\Models\ProjectFolder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FolderPermission>
 */
class FolderPermissionFactory extends Factory
{
    protected $model = FolderPermission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Permission rows live on top-level folders.
            'project_folder_id' => ProjectFolder::factory()->topLevel(),
            'role' => fake()->randomElement(['admin', 'contractor', 'customer']),
            'level' => fake()->randomElement(['read-write', 'read-only', 'no-access']),
        ];
    }
}
