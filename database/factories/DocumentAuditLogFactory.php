<?php

namespace Database\Factories;

use App\Models\DocumentAuditLog;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAuditLog>
 */
class DocumentAuditLogFactory extends Factory
{
    protected $model = DocumentAuditLog::class;

    /**
     * Define the model's default state (a folder-targeted entry).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => null,
            'action' => fake()->randomElement([
                'uploaded', 'downloaded', 'deleted', 'copied',
                'folder_created', 'folder_renamed', 'folder_reordered', 'folder_deleted',
            ]),
            'target_type' => fake()->randomElement(['document', 'folder']),
            'project_document_id' => null,
            'project_folder_id' => null,
            'metadata' => null,
            'created_at' => now(),
        ];
    }
}
