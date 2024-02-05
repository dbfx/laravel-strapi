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

return [
    // The url to your Strapi installation, e.g. https://strapi.yoursite.com/
    'url' => env('STRAPI_URL'),

    // How long to cache results for in seconds
    'cacheTime' => (int) env('STRAPI_CACHE_TIME', 3600),

    // Token for authentication
    'token' => env('STRAPI_TOKEN', null),
];
