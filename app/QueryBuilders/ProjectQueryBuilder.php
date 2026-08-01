<?php

namespace App\QueryBuilders;

use App\Models\Project;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProjectQueryBuilder
{
    /** @var Builder<Project> */
    private Builder $query;

    /**
     * @param  Builder<Project>  $query
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public static function forUser(Authenticatable $user): self
    {
        return new self(
            Project::query()
                ->with('attachments')
                ->where('user_id', (int) $user->getAuthIdentifier()),
        );
    }

    public function status(?string $status): self
    {
        if ($status !== null) {
            $this->query->where('status', $status);
        }

        return $this;
    }

    public function search(?string $search): self
    {
        if ($search !== null && $search !== '') {
            $this->query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $this;
    }

    public function latest(): self
    {
        $this->query->latest();

        return $this;
    }

    /**
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query->paginate($perPage);
    }

    /**
     * @return Builder<Project>
     */
    public function toBase(): Builder
    {
        return $this->query;
    }
}
