<?php

namespace Ceygenic\Blog\Traits;

use Illuminate\Support\Facades\Cache;

trait HasCache
{
    // Get cache key with prefix.
    protected function getCacheKey(string $key): string
    {
        $prefix = config('blog.cache.prefix', 'blog');
        return "{$prefix}:{$key}";
    }

    // Check if caching is enabled.
    protected function isCacheEnabled(?string $type = null): bool
    {
        if (!config('blog.cache.enabled', true)) {
            return false;
        }

        if ($type) {
            return config("blog.cache.queries.{$type}.enabled", true);
        }

        return true;
    }

    //Get cache TTL.
    protected function getCacheTtl(?string $type = null): int
    {
        if ($type) {
            return config("blog.cache.queries.{$type}.ttl", config('blog.cache.ttl', 3600));
        }

        return config('blog.cache.ttl', 3600);
    }

    //Remember cache value.
    protected function remember(string $key, callable $callback, ?string $type = null)
    {
        if (!$this->isCacheEnabled($type)) {
            return $callback();
        }

        $cacheKey = $this->getCacheKey($key);
        $ttl = $this->getCacheTtl($type);

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    // Forget cache by key.
    protected function forgetCache(string $key): void
    {
        $cacheKey = $this->getCacheKey($key);
        Cache::forget($cacheKey);
    }

    // Clear all blog cache.
    protected function clearAllCache(): void
    {
        $prefix = config('blog.cache.prefix', 'blog');
        Cache::flush(); // Note: This clears ALL cache. For production, use tag-based cache or specific key patterns
    }

    // Clear cache by pattern (requires cache driver that supports tags like Redis).
    protected function clearCacheByPattern(string $pattern): void
    {
        $prefix = config('blog.cache.prefix', 'blog');
        $fullPattern = "{$prefix}:{$pattern}";
        
        // Try to use cache tags if available (Redis, Memcached)
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags([$fullPattern])->flush();
        } else {
            // Fallback: clear all cache (not ideal, but works)
            Cache::flush();
        }
    }
}

