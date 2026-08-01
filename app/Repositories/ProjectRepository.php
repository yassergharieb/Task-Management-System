<?php

namespace App\Repositories;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use App\QueryBuilders\ProjectQueryBuilder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectRepository implements ProjectRepositoryInterface
{
    /**
     * @param  array{status?: string, search?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginateForUser(Authenticatable $user, array $filters = []): LengthAwarePaginator
    {
        return ProjectQueryBuilder::forUser($user)
            ->status($filters['status'] ?? null)
            ->search($filters['search'] ?? null)
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param  array{name: string, description?: string|null, status: string}  $data
     */
    public function createForUser(Authenticatable $user, array $data): Project
    {
        return Project::query()
            ->create([
                ...$data,
                'user_id' => (int) $user->getAuthIdentifier(),
            ])
            ->load('attachments');
    }

    /**
     * @param  array{name?: string, description?: string|null, status?: string}  $data
     */
    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->refresh()->load('attachments');
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
