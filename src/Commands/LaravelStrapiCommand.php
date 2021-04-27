<?php

namespace Dbfx\LaravelStrapi\Commands;

use Illuminate\Console\Command;

class LaravelStrapiCommand extends Command
{
    public $signature = 'laravel-strapi';

    public $description = 'My command';

    public function handle()
    {
        $this->comment('All done');
    }
}
