<?php

namespace App\Observers;

use App\Contracts\Services\CacheServiceInterface;
use App\Models\Task;
use App\Services\CacheGroups;

class TaskObserver
{
    public bool $afterCommit = true;

    public function __construct(
        private readonly CacheServiceInterface $cache,
    ) {}

    public function saved(Task $task): void
    {
        $this->clearTaskCache($task);
    }

    public function deleted(Task $task): void
    {
        $this->clearTaskCache($task);
    }

    private function clearTaskCache(Task $task): void
    {
        $task->loadMissing('project');

        $this->cache->removeGroup(CacheGroups::tasksForProject($task->project_id));

        if ($task->project !== null) {
            $this->cache->removeGroup(CacheGroups::projectsForProject($task->project));
            $this->cache->removeGroup(CacheGroups::dashboardForUser((int) $task->project->user_id));
        }
    }
}
