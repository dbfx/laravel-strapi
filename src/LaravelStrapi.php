<?php

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
            $this->headers['Authorization'] = 'Bearer ' . $this->token;
        }
    }

    public function collection(string $type, $sortKey = 'id', $sortOrder = 'DESC', $limit = 20, $start = 0, $fullUrls = true): array
    {
        $url = $this->strapiUrl;
        $cacheKey = self::CACHE_KEY . '.collection.' . $type . '.' . $sortKey . '.' . $sortOrder . '.' . $limit . '.' . $start;

        // Fetch and cache the collection type
        $collection = Cache::remember($cacheKey, $this->cacheTime, function () use ($url, $type, $sortKey, $sortOrder, $limit, $start) {
            $response = Http::withHeaders($this->headers)->get($url . '/' . $type . '?sort[0]=' . $sortKey . ':' . $sortOrder . '&pagination[limit]=' . $limit . '&pagination[start]=' . $start);

            return $response->json();
        });

        if (isset($collection['statusCode']) && $collection['statusCode'] >= 400) {
            Cache::forget($cacheKey);

            throw new PermissionDenied('Strapi returned a ' . $collection['statusCode']);
        }

        if (!is_array($collection)) {
            Cache::forget($cacheKey);

            if ($collection === null) {
                throw new NotFound('The requested single entry (' . $type . ') was null');
            }

            throw new UnknownError('An unknown Strapi error was returned');
        }

        // Replace any relative URLs with the full path
        if ($fullUrls) {
            $collection = $this->convertToFullUrls($collection);
        }

        return $collection;
    }

    public function collectionCount(string $type): int
    {
        $url = $this->strapiUrl;

        return Cache::remember(self::CACHE_KEY . '.collectionCount.' . $type, $this->cacheTime, function () use ($url, $type) {
            $response = Http::withHeaders($this->headers)->get($url . '/' . $type . '/count');

            return $response->json();
        });
    }

    public function entry(string $type, int $id, $fullUrls = true): array
    {
        $url = $this->strapiUrl;
        $cacheKey = self::CACHE_KEY . '.entry.' . $type . '.' . $id;

        $entry = Cache::remember($cacheKey, $this->cacheTime, function () use ($url, $type, $id) {
            $response = Http::withHeaders($this->headers)->get($url . '/' . $type . '/' . $id);

            return $response->json();
        });

        if (isset($entry['statusCode']) && $entry['statusCode'] >= 400) {
            Cache::forget($cacheKey);

            throw new PermissionDenied('Strapi returned a ' . $entry['statusCode']);
        }

        if (!isset($entry['id']) && !isset($entry['data']['id'])) {
            Cache::forget($cacheKey);

            if ($entry === null) {
                throw new NotFound('The requested single entry (' . $type . ') was null');
            }

            throw new UnknownError('An unknown Strapi error was returned');
        }

        if ($fullUrls) {
            $entry = $this->convertToFullUrls($entry);
        }

        return $entry;
    }

    public function entriesByField(string $type, string $fieldName, $fieldValue, $fullUrls = true, array $populate = array()): array
    {
        $url = $this->strapiUrl;
        $cacheKey = self::CACHE_KEY . '.entryByField.' . $type . '.' . $fieldName . '.' . $fieldValue;

        $entries = Cache::remember($cacheKey, $this->cacheTime, function () use ($url, $type, $fieldName, $fieldValue, $populate) {
            $populateString = '';
            foreach($populate as $key => $value) {
                $populateString = $populateString . '&populate[' . $key . ']=' . $value;
            }
                
            $response = Http::withHeaders($this->headers)->get($url . '/' . $type . '?filters[' . $fieldName . '][$eq]=' . $fieldValue . $populateString);
            
            return $response->json();
        });

        if (isset($entries['statusCode']) && $entries['statusCode'] >= 400) {
            Cache::forget($cacheKey);

            throw new PermissionDenied('Strapi returned a ' . $entries['statusCode']);
        }

        if (!is_array($entries)) {
            Cache::forget($cacheKey);

            if ($entries === null) {
                throw new NotFound('The requested entries by field (' . $type . ') were not found');
            }

            throw new UnknownError('An unknown Strapi error was returned');
        }

        if ($fullUrls) {
            $entries = $this->convertToFullUrls($entries);
        }

        return $entries;
    }

    public function single(string $type, string $pluck = null, $fullUrls = true)
    {
        $url = $this->strapiUrl;
        $cacheKey = self::CACHE_KEY . '.single.' . $type;

        // Fetch and cache the collection type
        $single = Cache::remember($cacheKey, $this->cacheTime, function () use ($url, $type) {
            $response = Http::withHeaders($this->headers)->get($url . '/' . $type);

            return $response->json();
        });

        if (isset($single['statusCode']) && $single['statusCode'] >= 400) {
            Cache::forget($cacheKey);

            throw new PermissionDenied('Strapi returned a ' . $single['statusCode']);
        }

        if (! isset($single['id'])) {
            Cache::forget($cacheKey);

            if ($single === null) {
                throw new NotFound('The requested single entry (' . $type . ') was null');
            }

            throw new UnknownError('An unknown Strapi error was returned');
        }

        // Replace any relative URLs with the full path
        if ($fullUrls) {
            $single = $this->convertToFullUrls($single);
        }

        if ($pluck !== null && isset($single[$pluck])) {
            return $single[$pluck];
        }

        return $single;
    }

    /**
     * This function adds the Strapi URL to the front of content in entries, collections, etc.
     * This is primarily used to change image URLs to actually point to Strapi.
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

            $array[$key] = preg_replace('/!\[(.*)\]\((.*)\)/', '![$1](' . config('strapi.url') . '$2)', $item);
        }

        return $array;
    }
}
