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
    public const CACHE_KEY = 'laravel-strapi-cache';

    private string $strapiUrl;
    private int $cacheTime;
    private $token;
    private array $headers = [];

    public function __construct()
    {
        $this->strapiUrl = config('strapi.url');
        $this->cacheTime = config('strapi.cacheTime');
        $this->token = config('strapi.token');

        if (!empty($this->token)) {
            $this->headers['Authorization'] = 'Bearer '.$this->token;
        }
    }

    public function collection(string $type, $sortKey = 'id', $sortOrder = 'DESC', $limit = 20, $start = 0, $fullUrls = true, array|string $populate = [], array $queryData = []): array
    {
        $endpoint = $this->strapiUrl.'/'.$type;

        $queryData['sort'][0] = $sortKey.':'.$sortOrder;
        $queryData['pagination']['limit'] = $limit;
        $queryData['pagination']['start'] = $start;

        if (!empty($populate)) {
            $queryData['populate'] = $populate;
        }

        $endpoint .= '?'.http_build_query($queryData);

        $cacheKey = self::CACHE_KEY.'.'.__FUNCTION__.'.'.encrypt($endpoint);

        // Fetch and cache the collection type
        $return = Cache::remember($cacheKey, $this->cacheTime, function () use ($endpoint) {
            $response = Http::withHeaders($this->headers)->get($endpoint);

            return $response->json();
        });

        if (isset($return['statusCode']) && $return['statusCode'] >= 400) {
            Cache::forget($cacheKey);

            throw new PermissionDenied('Strapi returned a '.$return['statusCode']);
        }

        if (!is_array($return)) {
            Cache::forget($cacheKey);

            if (null === $return) {
                throw new NotFound('The requested single entry ('.$type.') was null');
            }

            throw new UnknownError('An unknown Strapi error was returned');
        }

        // Replace any relative URLs with the full path
        if ($fullUrls) {
            $return = $this->convertToFullUrls($return);
        }

        return $return;
    }

    public function collectionCount(string $type): int
    {
        $endpoint = $this->strapiUrl.'/'.$type.'/count';

        $cacheKey = self::CACHE_KEY.'.'.__FUNCTION__.'.'.encrypt($endpoint);

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($endpoint) {
            $response = Http::withHeaders($this->headers)->get($endpoint);

            return $response->json();
        });
    }

    public function entry(string $type, int $id, $fullUrls = true, array|string $populate = [], array $queryData = []): array
    {
        $endpoint = $this->strapiUrl.'/'.$type.'/'.$id;

        if (!empty($populate)) {
            $queryData['populate'] = $populate;
        }

        $endpoint .= '?'.http_build_query($queryData);

        $cacheKey = self::CACHE_KEY.'.'.__FUNCTION__.'.'.encrypt($endpoint);

        $return = Cache::remember($cacheKey, $this->cacheTime, function () use ($endpoint) {
            $response = Http::withHeaders($this->headers)->get($endpoint);

            return $response->json();
        });

        if (isset($return['statusCode']) && $return['statusCode'] >= 400) {
            Cache::forget($cacheKey);

            throw new PermissionDenied('Strapi returned a '.$return['statusCode']);
        }

        if (!is_array($return)) {
            Cache::forget($cacheKey);

            if (null === $return) {
                throw new NotFound('The requested single entry ('.$type.') was null');
            }

            throw new UnknownError('An unknown Strapi error was returned');
        }

        if ($fullUrls) {
            $return = $this->convertToFullUrls($return);
        }

        return $return;
    }

    public function entriesByField(string $type, string $fieldName, $fieldValue, $fullUrls = true, array|string $populate = [], array $queryData = []): array
    {
        $endpoint = $this->strapiUrl.'/'.$type;

        $queryData['filters'][$fieldName]['$eq'] = $fieldValue;

        if (!empty($populate)) {
            $queryData['populate'] = $populate;
        }

        $endpoint .= '?'.http_build_query($queryData);

        $cacheKey = self::CACHE_KEY.'.'.__FUNCTION__.'.'.encrypt($endpoint);

        $entries = Cache::remember($cacheKey, $this->cacheTime, function () use ($endpoint) {
            $response = Http::withHeaders($this->headers)->get($endpoint);

            return $response->json();
        });

        if (isset($entries['statusCode']) && $entries['statusCode'] >= 400) {
            Cache::forget($cacheKey);

            throw new PermissionDenied('Strapi returned a '.$entries['statusCode']);
        }

        if (!is_array($entries)) {
            Cache::forget($cacheKey);

            if (null === $entries) {
                throw new NotFound('The requested entries by field ('.$type.') were not found');
            }

            throw new UnknownError('An unknown Strapi error was returned');
        }

        if ($fullUrls) {
            $entries = $this->convertToFullUrls($entries);
        }

        return $entries;
    }

    public function single(string $type, string $pluck = null, $fullUrls = true, array|string $populate = [], array $queryData = []): array
    {
        $endpoint = $this->strapiUrl.'/'.$type;

        if (!empty($populate)) {
            $queryData['populate'] = $populate;
        }

        $endpoint .= '?'.http_build_query($queryData);

        $cacheKey = self::CACHE_KEY.'.'.__FUNCTION__.'.'.encrypt($endpoint);

        // Fetch and cache the collection type
        $return = Cache::remember($cacheKey, $this->cacheTime, function () use ($endpoint) {
            $response = Http::withHeaders($this->headers)->get($endpoint);

            return $response->json();
        });

        if (isset($return['statusCode']) && $return['statusCode'] >= 400) {
            Cache::forget($cacheKey);

            throw new PermissionDenied('Strapi returned a '.$return['statusCode']);
        }

        if (!is_array($return)) {
            Cache::forget($cacheKey);

            if (null === $return) {
                throw new NotFound('The requested single entry ('.$type.') was null');
            }

            throw new UnknownError('An unknown Strapi error was returned');
        }

        // Replace any relative URLs with the full path
        if ($fullUrls) {
            $return = $this->convertToFullUrls($return);
        }

        if (null !== $pluck && isset($return[$pluck])) {
            return $return[$pluck];
        }

        return $return;
    }

    /**
     * Function to create new entries in the Strapi DB.
     */
    public function create(string $type, array $data): \stdClass
    {
        $endpoint = $this->strapiUrl.'/'.$type;
        $response = Http::withHeaders($this->headers)->post($endpoint, ['data' => $data]);

        return $response->json();
    }

    /**
     * Function to create new entries in the Strapi DB.
     */
    public function update(string $type, int|string $id, array $data): \stdClass
    {
        $endpoint = $this->strapiUrl.'/'.$type.'/'.$id;
        $response = Http::withHeaders($this->headers)->put($endpoint, ['data' => $data]);

        return $response->json();
    }

    /**
     * This function adds the Strapi URL to the front of content in entries, collections, etc.
     * This is primarily used to change image URLs to actually point to Strapi.
     *
     * @param mixed $array
     */
    private function convertToFullUrls($array): array
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
