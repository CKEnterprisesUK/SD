<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;

class ProjectAuditLogController extends Controller
{
    /**
     * Display the audit log entries for the given project.
     *
     * Admin-only (inline check). Entries are eager-loaded with their actor,
     * document, and folder relations and shown latest-first.
     */
    public function index(Project $project)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $logs = $project->auditLogs()
            ->with(['user', 'document', 'folder'])
            ->latest('created_at')
            ->paginate(25);

        return view('admin.projects.audit-log', [
            'project' => $project,
            'logs' => $logs,
        ]);
    }
}
