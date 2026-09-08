<?php

namespace App\Policies;

use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use App\Models\User;
use App\Services\PermissionResolver;

/**
 * Authorization for document viewing, downloading, and modifying operations.
 *
 * Reads (view/download) delegate to PermissionResolver::canRead against the
 * document's containing folder. Writes (upload/delete/copy) require
 * PermissionResolver::canWrite and a non-Complete project, mirroring the
 * EnsureProjectWritable middleware as defense in depth (req 2.1/2.2/2.4).
 */
class DocumentPolicy
{
    public function __construct(private readonly PermissionResolver $resolver)
    {
    }

    /**
     * View a document: read access to its folder.
     */
    public function view(User $user, ProjectDocument $document): bool
    {
        return $this->resolver->canRead($user, $document->folder);
    }

    /**
     * Download a document: read access to its folder.
     */
    public function download(User $user, ProjectDocument $document): bool
    {
        return $this->resolver->canRead($user, $document->folder);
    }

    /**
     * Upload a document into a folder: write access and non-Complete project.
     */
    public function upload(User $user, ProjectFolder $folder): bool
    {
        return $this->resolver->canWrite($user, $folder)
            && ! $folder->project->isComplete();
    }

    /**
     * Delete a document: write access to its folder and non-Complete project.
     */
    public function delete(User $user, ProjectDocument $document): bool
    {
        // Locked documents (shared, undeletable references) can never be
        // deleted from a project library — they are managed centrally on the
        // folder-template settings page.
        if ($document->isLocked()) {
            return false;
        }

        $folder = $document->folder;

        return $this->resolver->canWrite($user, $folder)
            && ! $folder->project->isComplete();
    }

    /**
     * Copy a document into a destination folder: write access to the
     * destination and non-Complete project.
     */
    public function copy(User $user, ProjectFolder $destination): bool
    {
        return $this->resolver->canWrite($user, $destination)
            && ! $destination->project->isComplete();
    }
}
