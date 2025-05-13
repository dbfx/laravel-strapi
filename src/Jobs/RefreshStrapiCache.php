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

namespace Dbfx\LaravelStrapi\Jobs;

use Dbfx\LaravelStrapi\LaravelStrapiTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RefreshStrapiCache implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use LaravelStrapiTrait;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $cacheKey,
        private readonly string $endpoint,
        private readonly array $queryParams,
        private readonly bool $fullUrls,
        private readonly string $strapiUrl,
        private readonly string $strapiToken,
        private readonly bool $debug
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Fetch fresh data from Strapi using the shared trait
        $freshData = $this->fetchResponse(
            $this->endpoint,
            $this->queryParams,
            $this->fullUrls,
            $this->strapiUrl,
            $this->strapiToken,
            $this->debug,
            false // Don't throw exceptions, just log errors
        );

        // Only update the cache if we got valid data
        if (null !== $freshData) {
            // Update the cache with the fresh data, forever (or until manually invalidated)
            Cache::forever($this->cacheKey, $freshData);
        }
    }
}
