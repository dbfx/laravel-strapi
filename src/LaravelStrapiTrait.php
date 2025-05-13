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

use Dbfx\LaravelStrapi\Exceptions\PermissionDenied;
use Dbfx\LaravelStrapi\Exceptions\UnknownError;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait LaravelStrapiTrait
{
    /**
     * Fetch data directly from Strapi API.
     *
     * @param string $endpoint        The API endpoint
     * @param array  $queryParams     Query parameters for the request
     * @param bool   $fullUrls        Whether to convert URLs to full URLs
     * @param string $strapiUrl       The base URL of the Strapi API
     * @param string $strapiToken     The API token for authentication
     * @param bool   $debug           Whether to enable debug mode
     * @param bool   $throwExceptions Whether to throw exceptions (true) or log errors (false)
     *
     * @return mixed The API response data or null if request failed
     */
    protected function fetchResponse(
        string $endpoint,
        array $queryParams,
        bool $fullUrls,
        string $strapiUrl,
        string $strapiToken,
        bool $debug,
        bool $throwExceptions = true
    ) {
        $response = Http::withOptions([
            'debug' => $debug,
        ])
            ->withToken($strapiToken)
            ->baseUrl($strapiUrl)
            ->withQueryParameters($queryParams)
            ->get($endpoint)
        ;

        // Handle standard HTTP errors
        if ($response->notFound()) {
            return null;
        }

        // Handle status code >= 400
        if ($response->failed()) {
            Log::warning('Strapi API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($throwExceptions) {
                throw new PermissionDenied($response);
            }

            return null;
        }

        try {
            $responseData = $response->json();

            return $fullUrls ? $this->convertToFullUrls(collect($responseData), $strapiUrl)->toArray() : $responseData;
        } catch (\Exception $e) {
            Log::warning($e->getMessage(), [
                'exception' => $e,
                'endpoint' => $endpoint,
            ]);

            if ($throwExceptions) {
                throw new UnknownError($response);
            }

            return null;
        }
    }

    /**
     * This function adds the Strapi URL to the front of content in entries, collections, etc.
     * This is primarily used to change image URLs to actually point to Strapi.
     *
     * @param Collection $collection The collection to process
     * @param string     $strapiUrl  The base URL of the Strapi API
     *
     * @return Collection The processed collection
     */
    protected function convertToFullUrls(Collection $collection, string $strapiUrl): Collection
    {
        return $collection->map(function ($item, $key) use ($strapiUrl) {
            if (is_array($item) || is_object($item)) {
                return $this->convertToFullUrls(collect($item), $strapiUrl);
            }

            return 'url' === $key ? $strapiUrl.$item : $item;
        });
    }
}
