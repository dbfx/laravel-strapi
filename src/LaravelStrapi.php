<?php

namespace Dbfx\LaravelStrapi;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LaravelStrapi
{
    private const CACHE_KEY = 'laravel-strapi';

    private $strapiUrl;
    private $cacheTime;

    public function __construct()
    {
        $this->strapiUrl = config('strapi.url');
        $this->cacheTime = config('strapi.cacheTime');
    }

    public function collection(string $type, $reverse = false)
    {
        $url = $this->strapiUrl;

        // Fetch and cache the collection type
        $collection = Cache::remember(self::CACHE_KEY . '.collection.' . $type, $this->cacheTime, function () use ($url, $type) {
            $response = Http::get($url . '/' . $type);

            return $response->json();
        });

        // Order it by latest post first
        if ($reverse) {
            $collection = array_reverse($collection);
        }

        return $collection;
    }
}
