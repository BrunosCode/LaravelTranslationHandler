# Changelog

All notable changes to `laravel-translation-handler` will be documented in this file.

## v2.1.2 - 2026-04-30

### Fixed

- `DatabaseHandler::handleUpdate()` no longer touches `translation_keys.updated_at` and `translation_values.updated_at` for rows whose value has not changed. Previously, writing a single translation through the MCP tools (or any path that funnels through `TranslationHandlerService::set()`) would refresh `updated_at` on every existing row in the table, because `set()` merges the input with the full existing collection before passing it to `put()`. The handler now skips key rows unless they are soft-deleted (and need reviving) and skips value rows whose `value` and `deleted_at` are unchanged.

## v2.1.1 - 2026-04-30

### Fixed

- MCP tools now return `Response` (text with JSON-encoded payload) instead of `ResponseFactory` (structured content). This fixes a `BadMethodCallException: Method Laravel\Mcp\ResponseFactory::isError does not exist` when the tools are executed in subprocess by `laravel/boost`'s `ExecuteToolCommand`, which expects a `Response` return value.

## v2.1.0 — Laravel Boost MCP integration - 2026-04-30

### Added

- **Laravel Boost MCP tools** for AI-assisted translation management. When `laravel/boost` is installed, the package auto-registers **seven tools** into Boost's MCP server (`packageBooted()` in `TranslationHandlerServiceProvider`):
  - `get-translation-config` — read the active translation handler config
  - `list-translations` — list translations from a storage format, optionally filtered by locale or key group prefix
  - `list-translation-groups` — list unique key groups at a given depth level (number of delimiters), with optional case-insensitive search; useful for exploring large key hierarchies before reading or writing
  - `find-translation` — look up a single translation by key and locale
  - `set-translation` — create or update a translation for a single locale
  - `set-all-locales-translation` — create or update a translation key for **all locales at once** in a single call
  - `sync-translations` — sync translations between storage formats (PHP / JSON / CSV / DB)
  
- **DB-first workflow recommendation** documented in the skill and README: write individual changes to `db` (row-level I/O), then flush to files with a single `sync-translations` call at the end, avoiding repeated full-file rewrites.
- **AI development skill + Boost guideline** under `resources/boost/guidelines/core.blade.php` and `resources/boost/skills/translation-handler-development/SKILL.md`, documenting tool contracts, translation handler conventions, and the DB-first workflow for AI agents.

### Dev

- `laravel/mcp ^0.7.0` added as a dev dependency. The runtime requirement is unchanged — Boost integration only activates when the host app installs `laravel/boost`.
- Feature test suite for each MCP tool covering all storage backends (PHP, JSON, CSV, DB).
- CI: `composer require ... --dev` so the new MCP dep resolves on the matrix.

### Fixed

- Test suite duplicated the package migrations (Spatie's published copies in the testbench app vs. a manual `loadMigrationsFrom` of `/tmp` stubs), causing `migrate:fresh` to fail with `table "translation_keys" already exists`. Removed the redundant `defineDatabaseMigrations()` override so tests rely on the workbench-published migrations only.

### Compatibility

- No breaking changes. Boost tools are opt-in via `laravel/boost`; without it, the service provider behaves exactly as in v2.0.3.
- Composer suggest entry: `laravel/boost — Required to expose translation MCP tools to AI agents via boost:mcp`.

## ## v2.0.3 - 2026-04-10 - 2026-04-10

### Fixed

- Allow nullable `value` property on `Translation` data class to match existing validator rule

## v2.0.3 - 2026-04-10

### Fixed

- Allow nullable `value` property on `Translation` data class to match existing validator rule

## v1.0.1 - 2026-04-10

### Fixed

- Allow nullable `value` property on `Translation` data class to match existing validator rule
- 

## v2.0.1 — Laravel 11 container compatibility fix - 2026-03-19

**Bug fix**: `setOption` / `setOptions` overrides were silently ignored when resolving file and database handlers under Laravel 11.

### What changed

`TranslationHandlerService` instantiates handlers by passing a `TranslationOptions` object to the Laravel service container:

```php
// before
app($class, [$this->getOptions()]);

// after
app($class, ['options' => $this->getOptions()]);



```
Laravel 11 removed the automatic conversion of positional parameters to named ones (`keyParametersByArgument`). As a result, the container ignored the provided `TranslationOptions` instance and auto-resolved a fresh one from config — discarding any runtime overrides set via `setOption()` or `setOptions()`.

### Impact

Any option overridden at runtime was silently ignored. The most visible symptom was the CSV delimiter: selecting `,` in the import/export UI still used the default `;` from config, causing:

```
Invalid CSV at line 2: expected at least 2 columns, got 1.
Check that the delimiter is ";"



```
The same issue affected all four handler factories (`getPhpHandler`, `getCsvHandler`, `getJsonHandler`, `getDbHandler`).

### Affected versions

- `^2.0` on **Laravel 11** (which ships with Illuminate 11.x).
- Laravel 10 was not affected because its container still supported positional parameter overrides.

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

Remove Laravel 10 required v0.1.2 - 2025-02-14

## v1.0.0 - 2026-03-18

### First stable release

Laravel Translation Handler is a Laravel package to move, import, and export translations across PHP files, JSON files, CSV files, and database — all via artisan commands or a clean Facade API.

### Features

- **Four translation formats**: PHP files, JSON files, CSV files, and database
- **Artisan commands**: `translation-handler`, `translation-handler:import`, `translation-handler:export`, `translation-handler:get`, `translation-handler:set`
- **Guided mode**: interactive `--guided` flag on import/export/move commands
- **`--fresh` option**: delete existing translations before writing
- **`--force` option**: overwrite existing translations
- **Custom paths**: `--from-path` / `--to-path` per command
- **Facade API**: `TranslationHandler::import()`, `::export()`, `::get()`, `::set()`, `::delete()`
- **Configurable options**: key delimiter, locales, file names, per-format paths, and custom handler classes
- **Nested JSON support**: `jsonNested` option to mirror PHP file structure in JSON output

### Improvements

- Fixed `--from` and `--to` option handling in `import` and `export` commands
- Improved error messages across all handlers for clearer diagnostics
- Added `--fresh` option to commands
- Nested translation tests

### Requirements

| Laravel | PHP        |
|---------|------------|
| 11.x    | 8.2, 8.3   |
| 10.x    | 8.2, 8.3   |

### CI

- Test matrix covers PHP 8.2, 8.3 × Laravel 10, 11
- Removed `prefer-lowest` stability from matrix (tests realistic dependency combinations only)


---

## v0.1.2 - 2025-02-14
