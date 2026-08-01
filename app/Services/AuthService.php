<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AuthServiceInterface;
use App\Exceptions\InvalidCredentialsException;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): UserResource
    {
        $user = $this->users->create($data);

        return $this->authenticatedUserResource($user);
    }

    /**
     * @param  array{email: string, password: string}  $credentials
     *
     * @throws InvalidCredentialsException
     */
    public function login(array $credentials): UserResource
    {
        $user = $this->users->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new InvalidCredentialsException;
        }

        return $this->authenticatedUserResource($user);
    }

    public function logout(?Authenticatable $user): void
    {
        if ($user instanceof User) {
            $user->currentAccessToken()->delete();
        }
    }

    private function authenticatedUserResource(User $user): UserResource
    {
        return new UserResource(
            resource: $user,
            token: $user->createToken('api-token')->plainTextToken,
        );
    }
}
