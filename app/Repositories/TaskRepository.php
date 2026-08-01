<?php

namespace App\Repositories;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Models\Project;
use App\Models\Task;
use App\QueryBuilders\TaskQueryBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * @param  array{status?: string, priority?: string, search?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginateForProject(Project $project, array $filters = []): LengthAwarePaginator
    {
        return TaskQueryBuilder::forProject($project)
            ->status($filters['status'] ?? null)
            ->priority($filters['priority'] ?? null)
            ->search($filters['search'] ?? null)
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param  array{title: string, description?: string|null, priority: string, status: string, due_date?: string|null}  $data
     */
    public function createForProject(Project $project, array $data): Task
    {
        return Task::query()
            ->create([
                ...$data,
                'project_id' => $project->id,
            ])
            ->load('attachments');
    }

    /**
     * @return array<int, int>
     */
    public function getIdsForProject(Project $project): array
    {
        return Task::query()
            ->where('project_id', $project->id)
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array{title?: string, description?: string|null, priority?: string, status?: string, due_date?: string|null}  $data
     */
    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->refresh()->load('attachments');
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }
}
