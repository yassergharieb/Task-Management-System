<?php

namespace App\Services;

use App\Contracts\Services\CacheServiceInterface;
use Illuminate\Support\Facades\Cache;

class CacheService implements CacheServiceInterface
{
    private const DEFAULT_TTL_SECONDS = 600;

    public function get(string $key): mixed
    {
        return Cache::get($key);
    }

    public function add(string $key, mixed $value, ?int $seconds = null): bool
    {
        return Cache::put($key, $value, $seconds ?? self::DEFAULT_TTL_SECONDS);
    }

    public function addToGroup(string $group, string $key, mixed $value, ?int $seconds = null): bool
    {
        $this->registerGroupKey($group, $key, $seconds);

        return $this->add($key, $value, $seconds);
    }

    public function remove(string $key): bool
    {
        return Cache::forget($key);
    }

    public function removeGroup(string $group): void
    {
        $indexKey = $this->groupIndexKey($group);
        $keys = Cache::get($indexKey, []);

        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (is_string($key)) {
                    $this->remove($key);
                }
            }
        }

        $this->remove($indexKey);
    }

    private function registerGroupKey(string $group, string $key, ?int $seconds = null): void
    {
        $indexKey = $this->groupIndexKey($group);
        $keys = Cache::get($indexKey, []);
        $keys = is_array($keys) ? $keys : [];
        $keys[] = $key;

        Cache::put(
            $indexKey,
            array_values(array_unique($keys)),
            $seconds ?? self::DEFAULT_TTL_SECONDS,
        );
    }

    private function groupIndexKey(string $group): string
    {
        return "cache-groups:{$group}";
    }
}
