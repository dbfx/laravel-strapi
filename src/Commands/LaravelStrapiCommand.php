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

namespace Dbfx\LaravelStrapi\Commands;

use Illuminate\Console\Command;

class LaravelStrapiCommand extends Command
{
    public $signature = 'strapi {task}';

    public $description = 'Laravel Strapi Helper';

    public function handle(): void {}
}
