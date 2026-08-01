<?php

namespace App\Contracts\Services;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

interface TaskServiceInterface
{
    /**
     * @param  array{status?: string, priority?: string, search?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Task>
     */
    public function list(Authenticatable $user, Project $project, array $filters = []): LengthAwarePaginator;

    /**
     * @param  array{title: string, description?: string|null, priority: string, status: string, due_date?: string|null, attachments?: array<int, UploadedFile>}  $data
     */
    public function create(Authenticatable $user, Project $project, array $data): Task;

    public function view(Authenticatable $user, Task $task): Task;

    /**
     * @param  array{title?: string, description?: string|null, priority?: string, status?: string, due_date?: string|null, attachments?: array<int, UploadedFile>}  $data
     */
    public function update(Authenticatable $user, Task $task, array $data): Task;

    public function delete(Authenticatable $user, Task $task): void;
}
