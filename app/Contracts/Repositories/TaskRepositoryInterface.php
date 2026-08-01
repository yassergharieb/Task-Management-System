<?php

namespace App\Contracts\Repositories;

use App\Models\Project;
use App\Models\Task;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    /**
     * @param  array{status?: string, priority?: string, search?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginateForProject(Project $project, array $filters = []): LengthAwarePaginator;

    /**
     * @param  array{title: string, description?: string|null, priority: string, status: string, due_date?: string|null}  $data
     */
    public function createForProject(Project $project, array $data): Task;

    /**
     * @return array<int, int>
     */
    public function getIdsForProject(Project $project): array;

    public function chunkOverduePendingNotifications(int $count, Closure $callback): bool;

    public function markOverdueNotified(Task $task): Task;

    /**
     * @param  array{title?: string, description?: string|null, priority?: string, status?: string, due_date?: string|null}  $data
     */
    public function update(Task $task, array $data): Task;

    public function delete(Task $task): void;
}
