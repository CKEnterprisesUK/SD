<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Authorization for Project management and viewing.
 *
 * Management actions (create/update/changeState/manage/viewAudit) are
 * admin-only. Viewing is scoped: admins see everything, customers see their
 * own customer's projects, and contractors see projects they are assigned to
 * via project_contractors. The scope check mirrors PermissionResolver's
 * project-scope logic.
 */
class ProjectPolicy
{
    /**
     * View a project: admin, owning customer, or assigned contractor.
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isCustomer()) {
            return $user->customer_id !== null
                && $user->customer_id === $project->customer_id;
        }

        if ($user->isContractor()) {
            $contractor = $user->contractor;

            if ($contractor === null) {
                return false;
            }

            return $project->contractors()
                ->where('contractors.id', $contractor->id)
                ->exists();
        }

        return false;
    }

    /**
     * Create a project: admin only.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Update a project: admin only.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    /**
     * Change a project's state: admin only.
     */
    public function changeState(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    /**
     * Manage a project (folders, contractors, structure): admin only.
     */
    public function manage(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    /**
     * View a project's audit log: admin only.
     */
    public function viewAudit(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }
}
