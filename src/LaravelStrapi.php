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

    public function collection(string $name, array $queryParams = [], int $cacheTime = null): array|int
    {
        $endpoint = '/api/'.$name;

        if (empty($queryParams['sort'])) {
            $queryParams['sort'] = config('strapi.sort.field', 'id').':'.config('strapi.sort.order', 'desc');
        }

        if (empty($queryParams['pagination'])) {
            $queryParams['pagination']['start'] = config('strapi.pagination.start', 0);
            $queryParams['pagination']['limit'] = config('strapi.pagination.limit', 25);
        }

        return $this->getResponse($endpoint, $queryParams, $cacheTime);
    }

    public function entry(string $name, int $id, array $queryParams = [], int $cacheTime = null): array|int
    {
        $endpoint = '/api/'.$name.'/'.$id;

        return $this->getResponse($endpoint, $queryParams, $cacheTime);
    }

    public function single(string $name, array $queryParams = [], int $cacheTime = null): array|int
    {
        $endpoint = '/api/'.$name;

        return $this->getResponse($endpoint, $queryParams, $cacheTime);
    }

    /**
     * Fetch and cache the collection type.
     */
    private function getResponse(string $endpoint, array $queryParams = [], int $cacheTime = null): array|int
    {
        $cacheKey = self::CACHE_KEY.'.'.encrypt($this->url.$endpoint.collect($queryParams)->toJson());

        return Cache::remember($cacheKey, $cacheTime ?? $this->cacheTime, function () use ($endpoint, $queryParams, $cacheKey) {
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

            if ($this->fullUrls) {
                $response = $this->convertToFullUrls($response);
            }

            return $response->json();
        });
    }

    /**
     * This function adds the Strapi URL to the front of content in entries, collections, etc.
     * This is primarily used to change image URLs to actually point to Strapi.
     *
     * @return int|(null|array|mixed|string)[]
     *
     * @psalm-return array<array|mixed|null|string>|int
     */
    private function convertToFullUrls(array|int $array): array|int
    {
        foreach ($array as $key => $item) {
            if (is_array($item)) {
                $array[$key] = $this->convertToFullUrls($item);
            }

            if (!is_string($item) || empty($item)) {
                continue;
            }

            $array[$key] = preg_replace('/!\[(.*)\]\((.*)\)/', '![$1]('.$this->url.'$2)', $item);
        }

        return $array;
    }
}
