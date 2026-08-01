<?php

namespace App\Services;

use App\Contracts\Services\CacheServiceInterface;
use App\Contracts\Services\DashboardServiceInterface;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\Auth\Authenticatable;

class DashboardService implements DashboardServiceInterface
{
    public function __construct(
        private readonly CacheServiceInterface $cache,
    ) {}

    /**
     * @return array{
     *     total_projects: int,
     *     active_projects: int,
     *     total_tasks: int,
     *     completed_tasks: int,
     *     pending_tasks: int,
     *     overdue_tasks: int
     * }
     */
    public function statistics(Authenticatable $user): array
    {
        $userId = (int) $user->getAuthIdentifier();
        $cacheKey = $this->cacheKey($userId);

        $cachedStatistics = $this->cache->get($cacheKey);

        if (is_array($cachedStatistics)) {
            return $cachedStatistics;
        }

        $statistics = $this->buildStatistics($userId);

        $this->cache->addToGroup(
            CacheGroups::dashboardForUser($userId),
            $cacheKey,
            $statistics,
            (int) config('cache.dashboard_ttl'),
        );

        return $statistics;
    }

    /**
     * @return array{
     *     total_projects: int,
     *     active_projects: int,
     *     total_tasks: int,
     *     completed_tasks: int,
     *     pending_tasks: int,
     *     overdue_tasks: int
     * }
     */
    private function buildStatistics(int $userId): array
    {
        $ownedTasks = Task::query()
            ->whereHas('project', fn ($query) => $query->where('user_id', $userId));

        return [
            'total_projects' => Project::query()
                ->where('user_id', $userId)
                ->count(),
            'active_projects' => Project::query()
                ->where('user_id', $userId)
                ->where('status', ProjectStatus::Active->value)
                ->count(),
            'total_tasks' => (clone $ownedTasks)->count(),
            'completed_tasks' => (clone $ownedTasks)
                ->where('status', TaskStatus::Done->value)
                ->count(),
            'pending_tasks' => (clone $ownedTasks)
                ->where('status', '!=', TaskStatus::Done->value)
                ->count(),
            'overdue_tasks' => (clone $ownedTasks)
                ->where('status', '!=', TaskStatus::Done->value)
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
        ];
    }

    private function cacheKey(int $userId): string
    {
        return "dashboard:statistics:user:{$userId}";
    }
}
