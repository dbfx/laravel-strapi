<?php

namespace Dbfx\LaravelStrapi;

use Dbfx\LaravelStrapi\Exceptions\NotFound;
use Dbfx\LaravelStrapi\Exceptions\PermissionDenied;
use Dbfx\LaravelStrapi\Exceptions\UnknownError;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LaravelStrapi
{
    public const CACHE_KEY = 'laravel-strapi';

    private $strapiUrl;
    private $cacheTime;

    public function __construct()
    {
        $this->strapiUrl = config('strapi.url');
        $this->cacheTime = config('strapi.cacheTime');
    }

    public function collection(string $type, $reverse = false, $fullUrls = true): array
    {
        $url = $this->strapiUrl;
        $cacheKey = self::CACHE_KEY . '.collection.' . $type;

        // Fetch and cache the collection type
        $collection = Cache::remember($cacheKey, $this->cacheTime, function () use ($url, $type) {
            $response = Http::get($url . '/' . $type);

            return $response->json();
        });

        if (isset($collection['statusCode']) && $collection['statusCode'] === 403) {
            Cache::forget($cacheKey);
            throw new PermissionDenied('Strapi returned a 403 Forbidden');
        }

        if (!is_array($collection)) {
            Cache::forget($cacheKey);

            if ($collection === null) {
                throw new NotFound('The requested single entry (' . $type . ') was null');
            }

            throw new UnknownError('An unknown Strapi error was returned');
        }

        // Order it by latest post first
        if ($reverse) {
            $collection = array_reverse($collection);
        }

        // Replace any relative URLs with the full path
        if ($fullUrls) {
            foreach ($collection as $key => $item) {
                foreach (array_keys($item) as $subKey) {
                    $collection[$key][$subKey] = preg_replace('/!\[(.*)\]\((.*)\)/', '![$1](' . config('strapi.url') . '$2)', $collection[$key][$subKey]);
                }
            }
        }

        return $collection;
    }

    public function collectionCount(string $type): int
    {
        $url = $this->strapiUrl;

        return Cache::remember(self::CACHE_KEY . '.collectionCount.' . $type, $this->cacheTime, function () use ($url, $type) {
            $response = Http::get($url . '/' . $type . '/count');

            return $response->json();
        });
    }

    public function entry(string $type, int $id, $fullUrls = true): array
    {
        $url = $this->strapiUrl;
        $cacheKey = self::CACHE_KEY . '.entry.' . $type . '.' . $id;

        $entry = Cache::remember($cacheKey, $this->cacheTime, function () use ($url, $type, $id) {
            $response = Http::get($url . '/' . $type . '/' . $id);

            return $response->json();
        });

        if (isset($entry['statusCode']) && $entry['statusCode'] === 403) {
            Cache::forget($cacheKey);
            throw new PermissionDenied('Strapi returned a 403 Forbidden');
        }

        if (!isset($entry['id'])) {
            Cache::forget($cacheKey);

            if ($entry === null) {
                throw new NotFound('The requested single entry (' . $type . ') was null');
            }

            throw new UnknownError('An unknown Strapi error was returned');
        }

        if ($fullUrls) {
            foreach ($entry as $key => $item) {
                $entry[$key] = preg_replace('/!\[(.*)\]\((.*)\)/', '![$1](' . config('strapi.url') . '$2)', $item);
            }
        }

        return $entry;
    }

    public function single(string $type, string $pluck = null, $fullUrls = true)
    {
        $url = $this->strapiUrl;
        $cacheKey = self::CACHE_KEY . '.single.' . $type;

        // Fetch and cache the collection type
        $single = Cache::remember($cacheKey, $this->cacheTime, function () use ($url, $type) {
            $response = Http::get($url . '/' . $type);

            return $response->json();
        });

        if (isset($single['statusCode']) && $single['statusCode'] === 403) {
            Cache::forget($cacheKey);
            throw new PermissionDenied('Strapi returned a 403 Forbidden');
        }

        if (!isset($single['id'])) {
            Cache::forget($cacheKey);

            if ($single === null) {
                throw new NotFound('The requested single entry (' . $type . ') was null');
            }

            throw new UnknownError('An unknown Strapi error was returned');
        }

        // Replace any relative URLs with the full path
        if ($fullUrls) {
            foreach ($single as $key => $item) {
                $single[$key] = preg_replace('/!\[(.*)\]\((.*)\)/', '![$1](' . config('strapi.url') . '$2)', $item);
            }
        }

        if ($pluck !== null && isset($single[$pluck])) {
            return $single[$pluck];
        }

        return $single;
    }
}
