<?php

namespace App\Contracts\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function create(array $data): User;

    public function findByEmail(string $email): ?User;
}
