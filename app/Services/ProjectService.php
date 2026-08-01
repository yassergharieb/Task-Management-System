<?php

namespace App\Services;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Contracts\Services\ProjectServiceInterface;
use App\Jobs\DeleteProjectAttachmentsJob;
use App\Models\Project;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectService implements ProjectServiceInterface
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
    ) {}

    /**
     * @param  array{status?: string, search?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Project>
     */
    public function list(Authenticatable $user, array $filters = []): LengthAwarePaginator
    {
        return $this->projects->paginateForUser($user, $filters);
    }

    /**
     * @param  array{name: string, description?: string|null, status: string, attachments?: array<int, UploadedFile>}  $data
     */
    public function create(Authenticatable $user, array $data): Project
    {
        $attachments = $this->pullAttachments($data);
        $project = $this->projects->createForUser($user, $data);

        $this->storeAttachments($project, $attachments);

        return $project->load('attachments');
    }

    public function view(Authenticatable $user, Project $project): Project
    {
        return $this->ensureOwnedProject($user, $project);
    }

    /**
     * @param  array{name?: string, description?: string|null, status?: string, attachments?: array<int, UploadedFile>}  $data
     */
    public function update(Authenticatable $user, Project $project, array $data): Project
    {
        $attachments = $this->pullAttachments($data);
        $project = $this->projects->update(
            $this->ensureOwnedProject($user, $project),
            $data,
        );

        $this->storeAttachments($project, $attachments);

        return $project->load('attachments');
    }

    public function delete(Authenticatable $user, Project $project): void
    {
        $project = $this->ensureOwnedProject($user, $project);

        DB::transaction(function () use ($project): void {
            /** @var array<int, int> $attachmentIds */
            $attachmentIds = $project->attachments
                ->pluck('id')
                ->all();

            $this->projects->delete($project);

            DeleteProjectAttachmentsJob::dispatch($attachmentIds)->afterCommit();
        });
    }

    private function ensureOwnedProject(Authenticatable $user, Project $project): Project
    {
        if ((int) $project->user_id !== (int) $user->getAuthIdentifier()) {
            throw new NotFoundHttpException('Project not found.');
        }

        return $project->load('attachments');
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
    private function storeAttachments(Project $project, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $path = $attachment->store("projects/{$project->id}", 'public');

            $project->attachments()->create([
                'name' => $attachment->getClientOriginalName(),
                'path' => $path,
                'disk' => 'public',
                'mime_type' => $attachment->getClientMimeType(),
                'size' => $attachment->getSize(),
            ]);
        }
    }
}
