<?php

namespace App\Services;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Contracts\Services\TaskServiceInterface;
use App\Jobs\DeleteProjectAttachmentsJob;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskService implements TaskServiceInterface
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    /**
     * @param  array{status?: string, priority?: string, search?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Task>
     */
    public function list(Authenticatable $user, Project $project, array $filters = []): LengthAwarePaginator
    {
        return $this->tasks->paginateForProject(
            $this->ensureOwnedProject($user, $project),
            $filters,
        );
    }

    /**
     * @param  array{title: string, description?: string|null, priority: string, status: string, due_date?: string|null, attachments?: array<int, UploadedFile>}  $data
     */
    public function create(Authenticatable $user, Project $project, array $data): Task
    {
        $attachments = $this->pullAttachments($data);
        $task = $this->tasks->createForProject(
            $this->ensureOwnedProject($user, $project),
            $data,
        );

        $this->storeAttachments($task, $attachments);

        return $task->load('attachments');
    }

    public function view(Authenticatable $user, Task $task): Task
    {
        return $this->ensureOwnedTask($user, $task);
    }

    /**
     * @param  array{title?: string, description?: string|null, priority?: string, status?: string, due_date?: string|null, attachments?: array<int, UploadedFile>}  $data
     */
    public function update(Authenticatable $user, Task $task, array $data): Task
    {
        $attachments = $this->pullAttachments($data);
        $task = $this->tasks->update(
            $this->ensureOwnedTask($user, $task),
            $data,
        );

        $this->storeAttachments($task, $attachments);

        return $task->load('attachments');
    }

    public function delete(Authenticatable $user, Task $task): void
    {
        $task = $this->ensureOwnedTask($user, $task);

        DB::transaction(function () use ($task): void {
            /** @var array<int, int> $attachmentIds */
            $attachmentIds = $task->attachments
                ->pluck('id')
                ->all();

            $this->tasks->delete($task);

            DeleteProjectAttachmentsJob::dispatch($attachmentIds)->afterCommit();
        });
    }

    private function ensureOwnedProject(Authenticatable $user, Project $project): Project
    {
        if ((int) $project->user_id !== (int) $user->getAuthIdentifier()) {
            throw new NotFoundHttpException('Project not found.');
        }

        return $project;
    }

    private function ensureOwnedTask(Authenticatable $user, Task $task): Task
    {
        $task->loadMissing('project', 'attachments');

        if ((int) $task->project->user_id !== (int) $user->getAuthIdentifier()) {
            throw new NotFoundHttpException('Task not found.');
        }

        return $task->load('attachments');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, UploadedFile>
     */
    private function pullAttachments(array &$data): array
    {
        $attachments = $data['attachments'] ?? [];

        unset($data['attachments']);

        return is_array($attachments) ? $attachments : [];
    }

    /**
     * @param  array<int, UploadedFile>  $attachments
     */
    private function storeAttachments(Task $task, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $path = $attachment->store("tasks/{$task->id}", 'public');

            $task->attachments()->create([
                'name' => $attachment->getClientOriginalName(),
                'path' => $path,
                'disk' => 'public',
                'mime_type' => $attachment->getClientMimeType(),
                'size' => $attachment->getSize(),
            ]);
        }
    }
}
