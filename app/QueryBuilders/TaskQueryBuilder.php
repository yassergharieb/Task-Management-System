<?php

namespace App\QueryBuilders;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TaskQueryBuilder
{
    /** @var Builder<Task> */
    private Builder $query;

    /**
     * @param  Builder<Task>  $query
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public static function forProject(Project $project): self
    {
        return new self(
            Task::query()
                ->with('attachments')
                ->where('project_id', $project->id),
        );
    }

    public function status(?string $status): self
    {
        if ($status !== null) {
            $this->query->where('status', $status);
        }

        return $this;
    }

    public function priority(?string $priority): self
    {
        if ($priority !== null) {
            $this->query->where('priority', $priority);
        }

        return $this;
    }

    public function search(?string $search): self
    {
        if ($search !== null && $search !== '') {
            $this->query->where('title', 'like', "%{$search}%");
        }

        return $this;
    }

    public function latest(): self
    {
        $this->query->latest();

        return $this;
    }

    /**
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query->paginate($perPage);
    }

    /**
     * @return Builder<Task>
     */
    public function toBase(): Builder
    {
        return $this->query;
    }
}
