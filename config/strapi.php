<?php

return [
    // The url to your Strapi installation, e.g. https://strapi.yoursite.com/
    'url' => env('STRAPI_URL'),

    // How long to cache results for in seconds
    'cacheTime' => env('STRAPI_CACHE_TIME', 3600),

    // Token for authentication
    'token' => env('STRAPI_TOKEN', null),
];
