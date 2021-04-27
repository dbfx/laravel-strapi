# Laravel wrapper for using the Strapi headless CMS

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dbfx/laravel-strapi.svg?style=flat-square)](https://packagist.org/packages/dbfx/laravel-strapi)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/dbfx/laravel-strapi/run-tests?label=tests)](https://github.com/dbfx/laravel-strapi/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/dbfx/laravel-strapi/Check%20&%20fix%20styling?label=code%20style)](https://github.com/dbfx/laravel-strapi/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/dbfx/laravel-strapi.svg?style=flat-square)](https://packagist.org/packages/dbfx/laravel-strapi)

---

Laravel-Strapi is a Laravel helper for using the Strapi headless CMS. 

## Installation

You can install the package via composer:

```bash
composer require dbfx/laravel-strapi
```

You can publish and run the migrations with:

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Dbfx\LaravelStrapi\LaravelStrapiServiceProvider" --tag="laravel-strapi-config"
```

You need to define your STRAPI_URL and STRAPI_CACHE_TIME in .env: 

```
STRAPI_URL=https://strapi.test.com
STRAPI_CACHE_TIME=3600
```

## Usage

```php
$strapi = new Dbfx\LaravelStrapi();
echo $strapi->echoPhrase('Hello, Spatie!');
```

## Testing

```bash
composer test
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
