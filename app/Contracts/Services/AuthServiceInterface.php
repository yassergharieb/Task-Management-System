<?php

namespace App\Contracts\Services;

use App\Http\Resources\UserResource;
use Illuminate\Contracts\Auth\Authenticatable;

interface AuthServiceInterface
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): UserResource;

    /**
     * @param  array{email: string, password: string}  $credentials
     */
    public function login(array $credentials): UserResource;

    public function logout(?Authenticatable $user): void;
}
