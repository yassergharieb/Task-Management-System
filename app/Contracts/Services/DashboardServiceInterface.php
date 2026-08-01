<?php

namespace App\Contracts\Services;

use Illuminate\Contracts\Auth\Authenticatable;

interface DashboardServiceInterface
{
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
    public function statistics(Authenticatable $user): array;
}
