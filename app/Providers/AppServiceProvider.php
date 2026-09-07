<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use App\Policies\DocumentPolicy;
use App\Policies\FolderPolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ProjectFolder::class, FolderPolicy::class);
        Gate::policy(ProjectDocument::class, DocumentPolicy::class);
    }
}
