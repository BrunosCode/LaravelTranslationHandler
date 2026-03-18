# Changelog

All notable changes to `laravel-translation-handler` will be documented in this file.

## v2.0.0 — Laravel 12 & PHP 8.4 Support** - 2026-03-18

> **Breaking change**: Laravel 10 is no longer supported. Please upgrade to Laravel 11 or 12.


---

### What's new

- **Laravel 12** support
- **PHP 8.4** support
- Updated `larastan` to `^3.0` and `phpstan` to `^2.0`

### Breaking changes

- **Dropped Laravel 10** — minimum supported version is now Laravel 11
- **Dropped PHP 8.1** — minimum supported PHP version is now 8.2

### Requirements

| Laravel | PHP |
|---------|-----|
| 12.x | 8.2, 8.3, 8.4 |
| 11.x | 8.2, 8.3, 8.4 |

### Upgrade from v1.x

If you are on Laravel 11 or 12, no code changes are required — update the package version in `composer.json`:

```bash
composer require brunoscode/laravel-translation-handler:^2.0

```
If you are on Laravel 10, you must upgrade Laravel before updating this package.


---

**Full Changelog**: https://github.com/BrunosCode/laravel-translation-handler/compare/v1.0.0...v2.0.0

## v1 - 2026-03-18

### What's Changed

* Bump aglipanci/laravel-pint-action from 2.5 to 2.6 by @dependabot[bot] in https://github.com/BrunosCode/LaravelTranslationHandler/pull/11
* Bump dependabot/fetch-metadata from 2.4.0 to 2.5.0 by @dependabot[bot] in https://github.com/BrunosCode/LaravelTranslationHandler/pull/15
* Bump actions/checkout from 4 to 6 by @dependabot[bot] in https://github.com/BrunosCode/LaravelTranslationHandler/pull/14
* Bump stefanzweifel/git-auto-commit-action from 6 to 7 by @dependabot[bot] in https://github.com/BrunosCode/LaravelTranslationHandler/pull/13

**Full Changelog**: https://github.com/BrunosCode/LaravelTranslationHandler/compare/v0.1.7...v1

## Remove Laravel 10 required v0.1.2 - 2025-02-14

Remove Laravel 10 required
