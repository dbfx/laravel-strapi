<?php

declare(strict_types=1);

/*
 * This file is part of the Laravel-Strapi helper.
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LaravelStrapi
{
    private const CACHE_KEY = 'laravel-strapi-cache';

    public bool $fullUrls;

    private readonly string $baseEnpoint;
    private readonly int $cacheTime;
    private readonly string $token;

    public function __construct()
    {
        $this->baseEnpoint = config('strapi.baseEnpoint');
        $this->cacheTime = config('strapi.cacheTime');
        $this->token = config('strapi.token');
        $this->fullUrls = config('strapi.fullUrls');
    }

    public function collection(string $name, array $queryData = [], int $cacheTime = null): array|int
    {
        $endpoint = $this->baseEnpoint.'/'.$name;

        if (empty($queryData['sort'])) {
            $queryData['sort'] = config('strapi.sort.field', 'id').':'.config('strapi.sort.order', 'desc');
        }

        if (empty($queryData['pagination'])) {
            $queryData['pagination']['start'] = config('strapi.pagination.start', 0);
            $queryData['pagination']['limit'] = config('strapi.pagination.limit', 25);
        }

        $endpoint .= '?'.http_build_query($queryData);

        return $this->getResponse($endpoint, $cacheTime);
    }

    public function collectionCount(string $name, array $queryData = [], int $cacheTime = null): array|int
    {
        $endpoint = $this->baseEnpoint.'/'.$name.'/count';

        if (!empty($queryData)) {
            $endpoint .= '?'.http_build_query($queryData);
        }

        return $this->getResponse($endpoint, $cacheTime);
    }

    public function entry(string $name, int $id, array $queryData = [], int $cacheTime = null): array|int
    {
        $endpoint = $this->baseEnpoint.'/'.$name.'/'.$id;

        if (!empty($queryData)) {
            $endpoint .= '?'.http_build_query($queryData);
        }

        return $this->getResponse($endpoint, $cacheTime);
    }

    public function single(string $name, array $queryData = [], int $cacheTime = null): array|int
    {
        $endpoint = $this->baseEnpoint.'/'.$name;

        if (!empty($queryData)) {
            $endpoint .= '?'.http_build_query($queryData);
        }

        return $this->getResponse($endpoint, $cacheTime);
    }

    /**
     * Fetch and cache the collection type.
     */
    private function getResponse(string $endpoint, int $cacheTime = null): array|int
    {
        $cacheKey = self::CACHE_KEY.'.'.encrypt($endpoint);

        $return = Cache::remember($cacheKey, $cacheTime ?? $this->cacheTime, fn () => Http::withToken($this->token)->get($endpoint)->json());

        if (isset($return['statusCode']) && $return['statusCode'] >= 400) {
            Cache::forget($cacheKey);

            throw new PermissionDenied(sprintf('Strapi returned a "%d" status code.', $return['statusCode']));
        }

        if (!is_int($return) && !is_array($return)) {
            Cache::forget($cacheKey);

            if (null === $return) {
                throw new NotFound('Strapi returned "null" response.');
            }

            throw new UnknownError('Strapi returned an unknown error.');
        }

        if ($this->fullUrls) {
            $return = $this->convertToFullUrls($return);
        }

        return $return;
    }

    /**
     * This function adds the Strapi URL to the front of content in entries, collections, etc.
     * This is primarily used to change image URLs to actually point to Strapi.
     */
    private function convertToFullUrls(array|int $array): array
    {
        foreach ($array as $key => $item) {
            if (is_array($item)) {
                $array[$key] = $this->convertToFullUrls($item);
            }

            if (!is_string($item) || empty($item)) {
                continue;
            }

            $array[$key] = preg_replace('/!\[(.*)\]\((.*)\)/', '![$1]('.config('strapi.url').'$2)', $item);
        }

        return $array;
    }
}
