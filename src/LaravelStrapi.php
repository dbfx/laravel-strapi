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

use Dbfx\LaravelStrapi\Exceptions\NotFound;
use Dbfx\LaravelStrapi\Exceptions\PermissionDenied;
use Dbfx\LaravelStrapi\Exceptions\UnknownError;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LaravelStrapi
{
    private const CACHE_KEY = 'laravel-strapi-cache';

    private const CACHE_DISABLED = 0;
    private const CACHE_FOREVER = null;

    public bool $fullUrls;

    private readonly string $url;
    private readonly int $cacheTime;
    private readonly string $token;
    private readonly bool $debug;

    public function __construct()
    {
        $this->url = config('strapi.url');
        $this->cacheTime = config('strapi.cacheTime');
        $this->token = config('strapi.token');
        $this->fullUrls = config('strapi.fullUrls');
        $this->debug = config('strapi.debug');
    }

    public function collection(string $name, array $queryParams = [], ?bool $fullUrls = null, ?int $cacheTime = null)
    {
        $endpoint = '/api/'.$name;

        $queryParams['sort'] ??= config('strapi.sort.field', 'id').':'.config('strapi.sort.order', 'desc');

        if (empty($queryParams['pagination'])) {
            $queryParams['pagination']['start'] = config('strapi.pagination.start', 0);
            $queryParams['pagination']['limit'] = config('strapi.pagination.limit', 25);
        }

        return $this->getResponse($endpoint, $queryParams, $fullUrls, $cacheTime);
    }

    public function entry(string $name, int|string $id, array $queryParams = [], ?bool $fullUrls = null, ?int $cacheTime = null)
    {
        $endpoint = '/api/'.$name.'/'.$id;

        return $this->getResponse($endpoint, $queryParams, $fullUrls, $cacheTime);
    }

    public function single(string $name, array $queryParams = [], ?bool $fullUrls = null, ?int $cacheTime = null)
    {
        $endpoint = '/api/'.$name;

        return $this->getResponse($endpoint, $queryParams, $fullUrls, $cacheTime);
    }

    /**
     * Fetch and cache the collection type.
     */
    private function getResponse(string $endpoint, array $queryParams = [], ?bool $fullUrls = null, ?int $cacheTime = null)
    {
        $cacheKey = $this->generateCacheKey($endpoint, $queryParams, $fullUrls);
        $effectiveCacheTime = $cacheTime ?? $this->cacheTime;
        
        // Handle cache strategies
        if ($effectiveCacheTime === self::CACHE_DISABLED) {
            // Skip cache completely
            return $this->fetchFromApi($endpoint, $queryParams, $fullUrls, $cacheKey);
        } elseif ($effectiveCacheTime === self::CACHE_FOREVER) {
            // Cache forever
            return Cache::rememberForever($cacheKey, fn() => $this->fetchFromApi($endpoint, $queryParams, $fullUrls, $cacheKey));
        } else {
            // Cache with expiration
            return Cache::remember($cacheKey, $effectiveCacheTime, fn() => $this->fetchFromApi($endpoint, $queryParams, $fullUrls, $cacheKey));
        }
    }
    
    /**
     * Generate a standardized cache key for consistent caching
     */
    private function generateCacheKey(string $endpoint, array $queryParams, ?bool $fullUrls): string
    {
        return Str::slug(self::CACHE_KEY).'_'.Str::toBase64($this->url.$endpoint.collect($queryParams)->toJson().(string)$fullUrls);
    }
    
    /**
     * Fetch data directly from API
     */
    private function fetchFromApi(string $endpoint, array $queryParams, ?bool $fullUrls, string $cacheKey)
    {
        $response = Http::withOptions([
            'debug' => $this->debug,
        ])
            ->withToken($this->token)
            ->baseUrl($this->url)
            ->withQueryParameters($queryParams)
            ->get($endpoint)
        ;

        if ($response->notFound()) {
            Cache::forget($cacheKey);
            
            return null;
        }

        // Unlike Guzzle's default behavior, Laravel's HTTP client wrapper does not throw exceptions
        // on client or server errors (400 and 500 level responses from servers)

        // Status code is >= 400
        if ($response->failed()) {
            $this->handleFailedResponse($response, $cacheKey, new PermissionDenied($response));
        }

        if (null === $response->body()) {
            $this->handleFailedResponse($response, $cacheKey, new NotFound($response));
        }

        if (!is_int($response->body()) && !is_array($response->body())) {
            $this->handleFailedResponse($response, $cacheKey, new UnknownError($response));
        }

        $responseData = $response->json();
        $shouldUseFullUrls = ($fullUrls ?? $this->fullUrls);
        
        return $shouldUseFullUrls ? $this->convertToFullUrls(collect($responseData))->toArray() : $responseData;
    }
    
    /**
     * Handle failed API responses
     */
    private function handleFailedResponse(Response $response, string $cacheKey, \Exception $exception): void
    {
        Cache::forget($cacheKey);
        throw $exception;
    }

    /**
     * This function adds the Strapi URL to the front of content in entries, collections, etc.
     * This is primarily used to change image URLs to actually point to Strapi.
     */
    private function convertToFullUrls(Collection $collection): Collection
    {
        // https://gist.github.com/brunogaspar/154fb2f99a7f83003ef35fd4b5655935
        return $collection->map(function ($item, $key) {
            if (is_array($item) || is_object($item)) {
                return $this->convertToFullUrls(collect($item));
            }

            return 'url' === $key ? $this->url.$item : $item;
        });
    }
}
