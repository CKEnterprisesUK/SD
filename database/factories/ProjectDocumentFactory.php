<?php

namespace Database\Factories;

use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectDocument>
 */
class ProjectDocumentFactory extends Factory
{
    protected $model = ProjectDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $original = fake()->word().'.'.fake()->randomElement(['pdf', 'docx', 'png', 'jpg']);

        return [
            'project_folder_id' => ProjectFolder::factory(),
            'uploaded_by_user_id' => null,
            'original_name' => $original,
            // Private-disk key only; never a public URL.
            'storage_path' => 'projects/'.fake()->numberBetween(1, 999)
                .'/'.fake()->numberBetween(1, 999)
                .'/'.strtolower(fake()->bothify('??????????????????????????')),
            'mime_type' => fake()->randomElement([
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/png',
                'image/jpeg',
            ]),
            'size_bytes' => fake()->numberBetween(0, 5_000_000),
        ];
    }
}
