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

    public function collection(string $type, $reverse = false, $fullUrls = true)
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

    public function entry(string $type, int $id, $fullUrls = true)
    {
        $url = $this->strapiUrl;

        $entry = Cache::remember(self::CACHE_KEY . '.entry.' . $type . '.' . $id, $this->cacheTime, function () use ($url, $type, $id) {
            $response = Http::get($url. '/' . $type . '/' . $id);

            return $response->json();
        });

        if ($fullUrls) {
            foreach ($entry as $key => $item) {
                $entry[$key] = preg_replace('/!\[(.*)\]\((.*)\)/', '![$1](' . config('strapi.url') . '$2)', $item);
            }
        }

        return $entry;
    }
}
