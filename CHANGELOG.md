# Changelog

All notable changes to `laravel-strapi` will be documented in this file.

## 4.0.1 - 2024-02-05

- Code refactoring, support for PHP 7.4 

## 4.0.0 - 2024-02-04

- Updates to latest Laravel
- Filter added to collection method

## 3.0.0 - 2022-11-07

- [BREAKING] Support for Strapi v4

## 2.0.2 - 2022-06-13

- Fix for issue https://github.com/dbfx/laravel-strapi/issues/16 with ['data'] response in entry()

## 2.0.0 - 2021-03-09

- Introduction of authentication, requires new strapi.php config update for token => STRAPI_TOKEN

## 1.2.0 - 2021-03-08

- Laravel 9 support

## 1.1.1 - 2021-12-21

- Fix recursive full image urls

## 1.1.0 - 2021-12-21

- Cleaned up the code base a little
- Should be more reliable now at converting to full URLs without errors

## 1.0.11 - 2021-10-02 

- Fix for another array to string issue

## 1.0.9 - 2021-09-13

- Fixes another problem with an array to string conversion error on single entries

## 1.0.8 - 2021-09-10

- Fixes a problem with an array to string conversion error on collections

## 1.0.7 - 2021-05-23

- Fixed a bug with forgetting collection caches

## 1.0.6 - 2021-04-29

- Added limit and start options to collections

## 1.0.5 - 2021-04-28

- Add the ability to sort and order collections by keys

## 1.0.4 - 2021-04-28

- Fixed a bug with cache times

## 1.0.3 - 2021-04-28

- Added entriesByField($type, $fieldName, $fieldValue)

## 1.0.2 - 2021-04-28

- Added collectionCount($type) method
- Added exceptions for errors
- Added single($type) to get a single item, optionally with $pluck to fetch a single value

## 1.0.1 - 2021-04-27

- fix an issue with caching specific entries

## 1.0.0 - 2021-04-26

- initial release
