<?php

namespace App\Repositories;

use App\Contracts\Repositories\AttachmentRepositoryInterface;
use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;

class AttachmentRepository implements AttachmentRepositoryInterface
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    /**
     * @return array<int, int>
     */
    public function getIdsForProjectWithTasks(Project $project): array
    {
        $taskIds = $this->tasks->getIdsForProject($project);

        return Attachment::query()
            ->where(function ($query) use ($project, $taskIds): void {
                $query
                    ->where(function ($query) use ($project): void {
                        $query
                            ->where('attachable_type', $project->getMorphClass())
                            ->where('attachable_id', $project->id);
                    })
                    ->orWhere(function ($query) use ($taskIds): void {
                        $query
                            ->where('attachable_type', (new Task)->getMorphClass())
                            ->whereIn('attachable_id', $taskIds);
                    });
            })
            ->pluck('id')
            ->toArray();
    }
}
