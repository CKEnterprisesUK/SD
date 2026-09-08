<?php

namespace App\Providers;

use App\Listeners\CustomerAuthActivitySubscriber;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectFolder;
use App\Policies\DocumentPolicy;
use App\Policies\FolderPolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Event;
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

        // Record customer portal sign-in / sign-out / password-reset events
        // into the customer activity feed.
        Event::subscribe(CustomerAuthActivitySubscriber::class);
    }
}
