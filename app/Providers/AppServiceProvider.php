<?php

namespace App\Providers;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AuthServiceInterface;
use App\Contracts\Services\ProjectServiceInterface;
use App\Models\Project;
use App\Models\User;
use App\Repositories\ProjectRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\ProjectService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(ProjectServiceInterface::class, ProjectService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'project' => Project::class,
            'user' => User::class,
        ]);
    }
}
