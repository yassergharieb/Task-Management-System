<?php

namespace App\Contracts\Services;

interface CacheServiceInterface
{
    public function get(string $key): mixed;

    public function add(string $key, mixed $value, ?int $seconds = null): bool;

    public function addToGroup(string $group, string $key, mixed $value, ?int $seconds = null): bool;

    public function remove(string $key): bool;

    public function removeGroup(string $group): void;
}
