<?php

declare(strict_types=1);

/*
 * This file is part of the Laravel-Strapi wrapper.
 *
 * (ɔ) Dave Blakey https://github.com/dbfx
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.md.
 */

namespace Dbfx\LaravelStrapi;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class LaravelStrapi
{
    use LaravelStrapiTrait;

    public const CACHE_TYPE_DISABLED = 'disabled';
    public const CACHE_TYPE_NORMAL = 'normal';
    public const CACHE_TYPE_FOREVER = 'forever';
    public const CACHE_TYPE_DEFERRED = 'deferred';
    public const CACHE_TYPE_FLEXIBLE = 'flexible';

    private const CACHE_KEY = 'laravel-strapi-cache';

    public bool $fullUrls;

    private readonly string $url;
    private readonly string $token;
    private readonly string $cacheType;
    private readonly int $cacheTime;
    private readonly int $cacheTtl;
    private readonly ?array $flexibleCache;
    private readonly string $queueConnection;
    private readonly bool $debug;

    public function __construct()
    {
        $this->url = config('strapi.url');
        $this->token = config('strapi.token');
        $this->cacheType = config('strapi.cache_type');
        $this->cacheTime = (int) config('strapi.cache_time');
        $this->cacheTtl = (int) config('strapi.cache_ttl');
        $this->flexibleCache = config('strapi.flexible_cache');
        $this->queueConnection = config('strapi.queue_connection');
        $this->fullUrls = (bool) config('strapi.full_urls');
        $this->debug = (bool) config('strapi.debug');

        // Verify the compatibility of cache_type and queue configuration
        if (self::CACHE_TYPE_DEFERRED === $this->cacheType && (empty($this->queueConnection) || 'null' === $this->queueConnection)) {
            Log::warning('Laravel-Strapi: CACHE_TYPE_DEFERRED selected, but no valid queue connection configured. Falling back to normal cache.');
            $this->cacheType = self::CACHE_TYPE_NORMAL;
        }
    }

    public function collection(string $name, array $queryParams = [], ?bool $fullUrls = null, ?int $cacheTime = null, ?string $cacheType = null)
    {
        $endpoint = '/api/'.$name;

        $queryParams['sort'] ??= config('strapi.sort.field').':'.config('strapi.sort.order');

        if (empty($queryParams['pagination'])) {
            $queryParams['pagination']['start'] = config('strapi.pagination.start');
            $queryParams['pagination']['limit'] = config('strapi.pagination.limit');
        }

        return $this->getResponse($endpoint, $queryParams, $fullUrls, $cacheTime, $cacheType);
    }

    public function entry(string $name, int|string $id, array $queryParams = [], ?bool $fullUrls = null, ?int $cacheTime = null, ?string $cacheType = null)
    {
        $endpoint = '/api/'.$name.'/'.$id;

        return $this->getResponse($endpoint, $queryParams, $fullUrls, $cacheTime, $cacheType);
    }

    public function single(string $name, array $queryParams = [], ?bool $fullUrls = null, ?int $cacheTime = null, ?string $cacheType = null)
    {
        $endpoint = '/api/'.$name;

        return $this->getResponse($endpoint, $queryParams, $fullUrls, $cacheTime, $cacheType);
    }

    /**
     * Fetch and cache the response from Strapi API.
     */
    private function getResponse(string $endpoint, array $queryParams = [], ?bool $fullUrls = null, ?int $cacheTime = null, ?string $cacheType = null)
    {
        $cacheKey = $this->generateCacheKey($endpoint, $queryParams, $fullUrls);
        $realCacheTime = $cacheTime ?? $this->cacheTime;
        $realCacheType = $cacheType ?? $this->cacheType;
        $realFullUrls = $fullUrls ?? $this->fullUrls;

        // Handle cache strategies based on cache type
        switch ($realCacheType) {
            case self::CACHE_TYPE_DISABLED:
                // Skip cache completely - but first let's delete any existing caches with this key
                Cache::forget($cacheKey);

                return $this->fetchFromApi($endpoint, $queryParams, $realFullUrls);

            case self::CACHE_TYPE_DEFERRED:
                // Check if queue is properly configured
                if (empty($this->queueConnection) || 'null' === $this->queueConnection) {
                    // Fall back to normal caching if queue is not configured
                    Log::warning('CACHE_TYPE_DEFERRED requires a valid queue connection. Falling back to normal cache.');

                    return Cache::remember(
                        $cacheKey,
                        $realCacheTime,
                        fn () => $this->fetchFromApi($endpoint, $queryParams, $realFullUrls)
                    );
                }

                // Deferred strategy - never make users wait
                return $this->getDeferredResponse($cacheKey, $endpoint, $queryParams, $realFullUrls);

            case self::CACHE_TYPE_FLEXIBLE:
                // Use flexible cache with stale-while-revalidate pattern
                $flexibleCache = [
                    $this->flexibleCache[0] ?? 300,  // Fresh period (default 5 minutes)
                    $this->flexibleCache[1] ?? 900,   // Total period (default 15 minutes)
                ];

                return Cache::flexible(
                    $cacheKey,
                    $flexibleCache,
                    fn () => $this->fetchFromApi($endpoint, $queryParams, $realFullUrls)
                );

            case self::CACHE_TYPE_FOREVER:
                // Cache forever (no expiration)
                return Cache::rememberForever(
                    $cacheKey,
                    fn () => $this->fetchFromApi($endpoint, $queryParams, $realFullUrls)
                );

            case self::CACHE_TYPE_NORMAL:
            default:
                // Standard cache with TTL expiration
                return Cache::remember(
                    $cacheKey,
                    $realCacheTime,
                    fn () => $this->fetchFromApi($endpoint, $queryParams, $realFullUrls)
                );
        }
    }

    /**
     * Wrapper for the trait fetchResponse method.
     */
    private function fetchFromApi(string $endpoint, array $queryParams, bool $fullUrls)
    {
        return $this->fetchResponse(
            $endpoint,
            $queryParams,
            $fullUrls,
            $this->url,
            $this->token,
            $this->debug,
            true
        );
    }

    /**
     * Fetch data with deferred strategy - never makes users wait for cache refresh.
     * Returns existing cache immediately while triggering background refresh if needed.
     */
    private function getDeferredResponse(string $cacheKey, string $endpoint, array $queryParams, bool $fullUrls)
    {
        // Check if background refresh is needed and trigger it if so
        $this->checkAndTriggerBackgroundRefresh($cacheKey, $endpoint, $queryParams, $fullUrls);

        // If cache doesn't exist (rare, only on first run), create it
        if (!Cache::has($cacheKey)) {
            return Cache::rememberForever($cacheKey, fn () => $this->fetchFromApi($endpoint, $queryParams, $fullUrls));
        }

        // Always return cached data immediately to ensure zero latency
        return Cache::get($cacheKey);
    }

    /**
     * Check if a background refresh of the cache is needed and trigger it if so.
     */
    private function checkAndTriggerBackgroundRefresh(string $cacheKey, string $endpoint, array $queryParams, bool $fullUrls): void
    {
        // Key to track the last update time for this cache
        $lastUpdateKey = $cacheKey.'-last-update';
        $now = now()->timestamp;

        // If no timestamp exists for last update, create cache and set timestamp
        if (!Cache::has($lastUpdateKey)) {
            $this->triggerBackgroundRefresh($cacheKey, $endpoint, $queryParams, $fullUrls);
            Cache::put($lastUpdateKey, $now, $this->cacheTtl);

            return;
        }

        $lastUpdate = Cache::get($lastUpdateKey);

        // If the last update was yesterday or earlier, trigger a refresh
        if (now()->startOfDay()->timestamp > Carbon::createFromTimestamp($lastUpdate)->startOfDay()->timestamp) {
            $this->triggerBackgroundRefresh($cacheKey, $endpoint, $queryParams, $fullUrls);
            Cache::put($lastUpdateKey, $now, $this->cacheTtl);
        }
    }

    /**
     * Trigger a background refresh of the cache.
     */
    private function triggerBackgroundRefresh(string $cacheKey, string $endpoint, array $queryParams, bool $fullUrls): void
    {
        // Check if queue is properly configured before attempting to dispatch
        if (!empty($this->queueConnection) && 'null' !== $this->queueConnection) {
            // Dispatch a job to refresh the cache in the background
            Queue::connection($this->queueConnection)->push(new Jobs\RefreshStrapiCache(
                $cacheKey,
                $endpoint,
                $queryParams,
                $fullUrls,
                $this->url,
                $this->token,
                $this->debug
            ));
        }
    }

    /**
     * Generate a standardized cache key for consistent caching.
     */
    private function generateCacheKey(string $endpoint, array $queryParams, ?bool $fullUrls): string
    {
        return Str::slug(self::CACHE_KEY).'_'.Str::toBase64($this->url.$endpoint.collect($queryParams)->toJson().(string) $fullUrls);
    }
}
