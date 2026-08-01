<?php

namespace App\Contracts\Repositories;

use App\Models\Project;

interface AttachmentRepositoryInterface
{
    /**
     * @return array<int, int>
     */
    public function getIdsForProjectWithTasks(Project $project): array;
}
