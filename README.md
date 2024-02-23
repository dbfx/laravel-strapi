# Laravel wrapper for using the Strapi headless CMS

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dbfx/laravel-strapi.svg?style=flat-square)](https://packagist.org/packages/dbfx/laravel-strapi)
[![Total Downloads](https://img.shields.io/packagist/dt/dbfx/laravel-strapi.svg?style=flat-square)](https://packagist.org/packages/dbfx/laravel-strapi)

---

**Laravel-Strapi** is a Laravel helper for using the [Strapi headless CMS](https://strapi.io/). 

_Note: for Strapi v3 support use version 2.x.x._

## Requirements

- PHP >= 8.2
- Laravel ^8 || ^9 || ^10

## Installation

1\) Install the package via composer:

```bash
composer require dbfx/laravel-strapi
```

2\) Publish the file `config/strapi.php` with:
```bash
php artisan vendor:publish --provider="Dbfx\LaravelStrapi\LaravelStrapiServiceProvider" --tag="strapi-config"
```

3\) Define this mandatory configuration value in your `.env` file:

```
STRAPI_BASE_ENDPOINT=https://api.example.com/api
```

Optionally you can also define these values:

```
STRAPI_TOKEN=abcd1234abcd1234
STRAPI_CACHE_TIME=3600
STRAPI_FULL_URLS=false
```

_Note: in `STRAPI_TOKEN` do not include `Bearer`, only the token itself._

## Usage

**Laravel-Strapi** provides these usefull methods:

```php
// returns collection-types rows by `$name`
public function collection(string $name, array $queryData = [], int $cacheTime = null): array|int

// returns collection-types count by `$name`
public function collectionCount(string $name, array $queryData = [], int $cacheTime = null): array|int

// returns collection-types row by `$name` and `$id`
public function entry(string $name, int $id, array $queryData = [], int $cacheTime = null): array|int

// returns single-types values by `$name`
public function single(string $name, array $queryData = [], int $cacheTime = null): array|int
```

These are all the available parameters:

- `$name` _(string)_: name of the collection-types (e.g. `blogs`) or single-types (e.g. `homepage`)
- `$id` _(int)_: id of a collection-types entry
- `$queryData` _(array)_: optional array of key-value pairs of REST API parameters (see here https://docs.strapi.io/dev-docs/api/rest/parameters)
- `$cacheTime` _(int)_: optional value in seconds to override the global value defined in `STRAPI_CACHE_TIME` per-call

## Examples

```php
use Dbfx\LaravelStrapi\LaravelStrapi;

$strapi = new LaravelStrapi();

// returns the first 25 rows of the collection-types `blogs`
// sorted in descending order by `id`
$rows = $strapi->collection('blogs');

// same thing as before, but in `en` language,
// sorted descending by field `date` and ascending by field `title`
// and with population of nested collection-types `author` and `images`
$rows = $strapi->collection('blogs', [
    'locale' => 'en',
    'sort' => [
        'date:desc',
        'title:asc',
    ],
    'populate' => [
        'author',
        'images',
    ],
]);

// similar to before, but with pagination by page values
$rows = $strapi->collection('blogs', [
    'pagination' => [
        'page' => 2,
        'pageSize' => 50,
    ],
]);

// similar to before, but with pagination by offset values
$rows = $strapi->collection('blogs', [
    'pagination' => [
        'start' => 0,
        'limit' => 100,
    ],
]);

// returns all rows of the collection-types `blogs`
// where field `slug` is equal to `test-blog-post`
$rows = $strapi->collection('blogs', [
    'filters' => [
        'slug' => [
            '$eq' => 'test-blog-post',
        ],
    ],
    'pagination' => [
        'start' => 0,
        'limit' => 1,
    ],
]);

// returns the row of the collection-types `blogs`
// where field `id` is `1`
$row = $strapi->entry('blogs', 1);

// returns all values of the single-types `homepage`
$rows = $strapi->single('homepage');
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Credits

- [Dave Blakey](https://github.com/dbfx)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
