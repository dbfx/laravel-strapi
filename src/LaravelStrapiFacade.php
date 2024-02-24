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

namespace Dbfx\LaravelStrapi;

use Illuminate\Support\Facades\Facade;

/**
 * @see LaravelStrapi
 */
class LaravelStrapiFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-strapi';
    }
}
