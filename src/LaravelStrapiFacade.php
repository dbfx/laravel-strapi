<?php

namespace Dbfx\LaravelStrapi;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Dbfx\LaravelStrapi\LaravelStrapi
 */
class LaravelStrapiFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-strapi';
    }
}
