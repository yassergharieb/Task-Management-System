<?php

namespace App\Contracts\Repositories;

use App\Models\Project;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface
{
    /**
     * @param  array{status?: string, search?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginateForUser(Authenticatable $user, array $filters = []): LengthAwarePaginator;

    /**
     * @param  array{name: string, description?: string|null, status: string}  $data
     */
    public function createForUser(Authenticatable $user, array $data): Project;

    /**
     * @param  array{name?: string, description?: string|null, status?: string}  $data
     */
    public function update(Project $project, array $data): Project;

    public function delete(Project $project): void;
}
