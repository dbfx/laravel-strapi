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

class LaravelStrapi
{
    private const CACHE_KEY = 'laravel-strapi-cache';

    public bool $fullUrls;

    private readonly string $url;
    private readonly int $cacheTime;
    private readonly string $token;

    public function __construct()
    {
        $this->url = config('strapi.url');
        $this->cacheTime = config('strapi.cacheTime');
        $this->token = config('strapi.token');
        $this->fullUrls = config('strapi.fullUrls');
    }

    public function collection(string $name, array $queryParams = [], bool $fullUrls = null, int $cacheTime = null)
    {
        $endpoint = '/api/'.$name;

        if (empty($queryParams['sort'])) {
            $queryParams['sort'] = config('strapi.sort.field', 'id').':'.config('strapi.sort.order', 'desc');
        }

        if (empty($queryParams['pagination'])) {
            $queryParams['pagination']['start'] = config('strapi.pagination.start', 0);
            $queryParams['pagination']['limit'] = config('strapi.pagination.limit', 25);
        }

        return $this->getResponse($endpoint, $queryParams, $fullUrls, $cacheTime);
    }

    public function entry(string $name, int $id, array $queryParams = [], bool $fullUrls = null, int $cacheTime = null)
    {
        $endpoint = '/api/'.$name.'/'.$id;

        return $this->getResponse($endpoint, $queryParams, $fullUrls, $cacheTime);
    }

    public function single(string $name, array $queryParams = [], bool $fullUrls = null, int $cacheTime = null)
    {
        $endpoint = '/api/'.$name;

        return $this->getResponse($endpoint, $queryParams, $fullUrls, $cacheTime);
    }

    /**
     * Fetch and cache the collection type.
     */
    private function getResponse(string $endpoint, array $queryParams = [], bool $fullUrls = null, int $cacheTime = null)
    {
        $cacheKey = self::CACHE_KEY.'.'.encrypt($this->url.$endpoint.collect($queryParams)->toJson());

        return Cache::remember($cacheKey, $cacheTime ?? $this->cacheTime, function () use ($endpoint, $queryParams, $fullUrls, $cacheKey) {
            $response = Http::withOptions([
                'debug' => config('app.debug'),
            ])
                ->withToken($this->token)
                ->baseUrl($this->url)
                ->withQueryParameters($queryParams)
                ->get($endpoint)
            ;

            // Unlike Guzzle's default behavior, Laravel's HTTP client wrapper does not throw exceptions
            // on client or server errors (400 and 500 level responses from servers)

            // status code is >= 400
            if ($response->failed()) {
                $response->throw(function (Response $response, RequestException $e) use ($cacheKey): void {
                    Cache::forget($cacheKey);

                    throw new PermissionDenied($response);
                });
            }

            if (null === $response->body()) {
                $response->throw(function (Response $response, RequestException $e) use ($cacheKey): void {
                    Cache::forget($cacheKey);

                    throw new NotFound($response);
                });
            }

            if (!is_int($response->body()) && !is_array($response->body())) {
                $response->throw(function (Response $response, RequestException $e) use ($cacheKey): void {
                    Cache::forget($cacheKey);

                    throw new UnknownError($response);
                });
            }

            return ($fullUrls ?? $this->fullUrls) ? $this->convertToFullUrls(collect($response->json()))->toArray() : $response->json();
        });
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
