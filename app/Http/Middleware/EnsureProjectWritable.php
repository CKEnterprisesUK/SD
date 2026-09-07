<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectWritable
{
    /**
     * Handle an incoming request.
     *
     * Aborts with a 403 read-only error when the resolved Project is in the
     * "Complete" state, so no folder/document modifying operation can proceed.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');

        // Route-model binding usually gives us a Project instance, but the
        // route parameter may also be a raw identifier (string/int). Resolve
        // it to a model in that case so the state check is always accurate.
        if ($project !== null && ! $project instanceof Project) {
            $project = Project::find($project);
        }

        if ($project instanceof Project && $project->isComplete()) {
            abort(Response::HTTP_FORBIDDEN, 'This project is complete and read-only.');
        }

        return $next($request);
    }
}
