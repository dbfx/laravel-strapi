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

namespace Dbfx\LaravelStrapi\Exceptions;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

class UnknownError extends RequestException
{
    /**
     * Prepare the exception message.
     *
     * @return string
     */
    protected function prepareMessage(Response $response)
    {
        return "Strapi HTTP request returned an unknown error with status code {$response->status()}";
    }
}
