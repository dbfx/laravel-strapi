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
    // The url to your Strapi installation, e.g. https://api.example.com
    'url' => env('STRAPI_URL', 'http://localhost:1337'),

    // Token for authentication
    'token' => env('STRAPI_TOKEN', null),

    // How long to cache results for in seconds
    'cacheTime' => (int) env('STRAPI_CACHE_TIME', 3600),

    // Replace any relative URLs with the full path
    'fullUrls' => (bool) env('STRAPI_FULL_URLS', false),
];
