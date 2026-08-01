<?php

namespace App\Observers;

use App\Contracts\Services\CacheServiceInterface;
use App\Models\Project;
use App\Services\CacheGroups;

class ProjectObserver
{
    public bool $afterCommit = true;

    public function __construct(
        private readonly CacheServiceInterface $cache,
    ) {}

    public function saved(Project $project): void
    {
        $this->clearProjectCache($project);
    }

    public function deleted(Project $project): void
    {
        $this->clearProjectCache($project);
        $this->cache->removeGroup(CacheGroups::tasksForProject($project->id));
    }

    private function clearProjectCache(Project $project): void
    {
        $this->cache->removeGroup(CacheGroups::projectsForProject($project));
        $this->cache->removeGroup(CacheGroups::dashboardForUser((int) $project->user_id));
    }
}
