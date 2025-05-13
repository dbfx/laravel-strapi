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

return [
    /*
    |--------------------------------------------------------------------------
    | Strapi URL
    |--------------------------------------------------------------------------
    |
    | URL to your Strapi instance including protocol and host (e.g. https://api.example.com).
    |
    */
    'url' => env('STRAPI_URL', 'http://localhost:1337'),

    /*
    |--------------------------------------------------------------------------
    | Strapi Token
    |--------------------------------------------------------------------------
    |
    | API Token for authenticating with Strapi. Can be generated in the Strapi admin.
    | Settings > API Tokens > Create new API Token
    |
    */
    'token' => env('STRAPI_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Caching Strategy
    |--------------------------------------------------------------------------
    |
    | Configure how responses from Strapi should be cached. Available options:
    |
    | - 'disabled': No caching, always fetch fresh data from Strapi
    | - 'normal': Standard TTL-based caching (expires after 'cache_time' minutes)
    | - 'forever': Cache never expires (until manually cleared)
    | - 'deferred': Returns cached data immediately while refreshing in background
    | - 'flexible': Uses stale-while-revalidate pattern (see 'flexible_cache')
    |
    */
    'cache_type' => env('STRAPI_CACHE_TYPE', 'normal'),

    /*
    |--------------------------------------------------------------------------
    | Cache Duration (in seconds)
    |--------------------------------------------------------------------------
    |
    | How long to keep cached responses (in seconds) when using 'normal' cache type.
    | This setting is ignored for other cache types.
    |
    | This defines the expiration time for standard TTL-based caching.
    |
    */
    'cache_time' => env('STRAPI_CACHE_TIME', 86400), // 24 hours by default

    /*
    |--------------------------------------------------------------------------
    | Deferred Cache TTL Management (in seconds)
    |--------------------------------------------------------------------------
    |
    | This setting controls how often a background refresh check is performed
    | when using the 'deferred' cache type.
    |
    | The cache itself never expires with deferred caching, but this TTL value
    | determines how frequently the system checks if it should trigger a
    | background refresh job.
    |
    */
    'cache_ttl' => env('STRAPI_CACHE_TTL', 86400), // 24 hours by default

    /*
    |--------------------------------------------------------------------------
    | Flexible Cache Configuration [fresh_period, total_period] (in seconds)
    |--------------------------------------------------------------------------
    |
    | Used with the 'flexible' cache type. Defines two periods in seconds:
    | 1. fresh_period: How long content is considered "fresh" (always served directly)
    | 2. total_period: How long content can be served stale while revalidating
    |
    | Example: [300, 900] means content is fresh for 5 minutes, then serves stale
    | content for up to 10 more minutes while refreshing in background.
    |
    */
    'flexible_cache' => [
        env('STRAPI_FLEXIBLE_CACHE_FRESH', 300),  // 5 minutes fresh period
        env('STRAPI_FLEXIBLE_CACHE_TOTAL', 900),  // 15 minutes total (including stale)
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Connection for Background Processing
    |--------------------------------------------------------------------------
    |
    | Queue connection to use for background refresh jobs when using 'deferred'
    | cache strategy. Must be a valid connection from your queue.php config.
    |
    | Required for 'deferred' cache type. If not configured properly, the system
    | will fall back to 'normal' caching.
    |
    */
    'queue_connection' => env('STRAPI_QUEUE_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Full URLs
    |--------------------------------------------------------------------------
    |
    | When true, automatically prefixes URLs in Strapi responses with the
    | configured Strapi URL (e.g. for images).
    |
    */
    'full_urls' => env('STRAPI_FULL_URLS', false),

    /*
    |--------------------------------------------------------------------------
    | Sorting Configuration
    |--------------------------------------------------------------------------
    |
    | Default sorting for collection requests.
    |
    */
    'sort' => [
        'field' => env('STRAPI_SORT_FIELD', 'id'),
        'order' => env('STRAPI_SORT_ORDER', 'desc'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | Default pagination parameters for collection requests.
    |
    */
    'pagination' => [
        'start' => env('STRAPI_PAGINATION_START', 0),
        'limit' => env('STRAPI_PAGINATION_LIMIT', 25),
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, outputs additional information about HTTP requests to Strapi.
    |
    */
    'debug' => env('STRAPI_DEBUG', false),
];
