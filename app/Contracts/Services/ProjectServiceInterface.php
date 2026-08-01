<?php

namespace App\Contracts\Services;

use App\Models\Project;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

interface ProjectServiceInterface
{
    /**
     * @param  array{status?: string, search?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Project>
     */
    public function list(Authenticatable $user, array $filters = []): LengthAwarePaginator;

    /**
     * @param  array{name: string, description?: string|null, status: string, attachments?: array<int, UploadedFile>}  $data
     */
    public function create(Authenticatable $user, array $data): Project;

    public function view(Authenticatable $user, Project $project): Project;

    /**
     * @param  array{name?: string, description?: string|null, status?: string, attachments?: array<int, UploadedFile>}  $data
     */
    public function update(Authenticatable $user, Project $project, array $data): Project;

    public function delete(Authenticatable $user, Project $project): void;
}
