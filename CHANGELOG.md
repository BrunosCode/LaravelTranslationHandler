# Changelog

All notable changes to `laravel-translation-handler` will be documented in this file.

## v2.3.0 - 2026-05-21

### Added

- **Linter-friendly writes** — File handlers (PHP, JSON, CSV) now compare the new content against what's already on disk before writing. If the result is identical, including key order, the write is skipped entirely. A file already formatted by the user's linter (Pint, PHP-CS-Fixer, Prettier, …) is left untouched as long as its translations and ordering match the new state.
- **`Concerns\ComparesTranslations` trait** — strict-equality check (via `json_encode`) plus a leaf-level diff counter, used by all three file handlers.
- **Idempotency tests** — every file handler now has a `returns 0 when content is unchanged` regression test.

### Changed

- **Return semantics of `put()` / `set()`** — the integer returned now represents the number of translations actually **changed** (added, modified, or removed), not the total count in the input collection. A no-op write returns `0`.
- **`sync()` / `import()` / `export()` return type** — changed from `bool` to `false|int`. `false` signals an actual failure (the source has no translations to read); any integer (including `0`) signals success and reports how many translations were changed in the destination.
- **`SyncCommand`, `ImportCommand`, `ExportCommand`, and the deprecated bare `Command`** — after the main success message, the command now prints either `Already in sync.` or `N translation(s) changed.`.
- **`SetCommand`** — when `set()` returns `0`, the command checks whether the current value already matches the requested one. If it does, the command prints `Translation already set!` and returns `SUCCESS` (previously this case was reported as `FAILURE`).
- **MCP tools** — `set-translation-tool` now also exposes the integer `count` field next to `written`; `sync-translations-tool` now exposes `success` (bool) and `count` (int).

### Why

The previous behavior rewrote every file on every operation, even when nothing had changed. Combined with code formatters that normalize array syntax / indentation / quoting, this produced spurious diffs in git on every sync and forced the formatter to run again afterwards. Comparing parsed content (not raw bytes) makes the operation a true no-op when the resulting state matches.

## v2.2.0 - 2026-05-10

### New Commands

- **`translation-handler:sync`** — Syncs translations between formats. The old bare `translation-handler` command is now deprecated in favour of this.
- **`translation-handler:list`** — Lists translations from a format, with optional `--locale` and `--group` filters.
- **`translation-handler:list-groups`** — Lists unique key group prefixes at a configurable depth, with optional `--search` filter.
- **`translation-handler:find`** — Finds a specific key+locale and prints it as a table.
- **`translation-handler:delete`** — Deletes a translation key. Use `--locale` to target a single locale, omit it to delete all locales.
- **`translation-handler:delete-group`** — Deletes all keys under a group prefix.
- **`translation-handler:sort`** — Sorts translation keys alphabetically in PHP, JSON, and CSV formats. Supports `--locale` and `--group` filters to restrict the scope.

### New MCP Tools

Three new tools are available to AI agents via Laravel Boost:

- **`delete-translation-tool`** — Deletes a single key, optionally for a specific locale only.
- **`delete-translation-group-tool`** — Deletes all keys under a group prefix.
- **`sort-translations-tool`** — Sorts keys alphabetically in PHP, JSON, or CSV, with optional locale and group filters.

### New Facade Methods

```php
TranslationHandler::deleteKey(from: 'php_file', key: 'auth.welcome', locale: 'en');
TranslationHandler::deleteGroup(from: 'php_file', group: 'auth');
TranslationHandler::sortKeys(from: 'php_file', locales: ['en'], groups: ['auth']);
```

### Bug Fixes

- **PHP and CSV handlers**: removed a redundant `array_replace_recursive` merge in `put()`. The service already merges the full collection before writing — the file-level re-merge was causing deleted keys to reappear after a write.
- **PHP handler**: when all translations for a locale file are removed, the file is now deleted instead of silently skipped (which left stale data on disk).
- **Database handler**: `handleSoftDelete` now also soft-deletes individual locale values when a key is partially deleted (e.g. removing only the `en` value while keeping `it`). Previously only whole-key deletion was supported.

## v2.1.2 — Timestamp isolation + set-translation-group MCP tool - 2026-04-30

### Added

- **`set-translation-group-tool`** — new MCP tool that translates an entire group in a single call. The AI provides a group prefix and an object of `subkey → {locale: value}`; the tool joins each subkey to the group with the configured key delimiter and writes all locale values for every subkey in one operation. Tolerates a trailing delimiter on the group (`auth.` ≡ `auth`) and supports nested subkeys (`nested.deep`). Brings the total MCP tool count to **8**.

  Example request:

  ```json
  {
    "format": "db",
    "group": "auth",
    "translations": {
      "welcome": {"en": "Welcome", "it": "Benvenuto"},
      "logout":  {"en": "Logout",  "it": "Esci"}
    },
    "force": true
  }
  ```

### Fixed

- `DatabaseHandler::handleUpdate()` no longer touches `translation_keys.updated_at` and `translation_values.updated_at` for rows whose value has not changed.

  Before this fix, writing a single translation through any MCP tool refreshed `updated_at` on every existing row in the table. Cause: `TranslationHandlerService::set()` reads the full existing collection, merges the input, then passes the entire merged collection to `put()` — and `handleUpdate` upserted every row in the input with `updated_at = now()`. The handler now:

  - skips `translation_keys` rows unless they are soft-deleted (and need reviving)
  - skips `translation_values` rows whose `value` and `deleted_at` are unchanged

### Tests

- `SetTranslationDbTimestampTest` — regression covering single-write timestamp isolation through `set-translation-tool` and `set-all-locales-translation-tool` against the DB format
- `SetTranslationGroupToolTest` — 10 cases covering write semantics, force flag, trailing delimiter, nested subkeys, and error paths

### Compatibility

- No breaking changes
- File-based handlers (PHP/JSON/CSV) are unaffected by the timestamp fix (they always rewrite the whole file)

## v2.1.1 — Laravel Boost subprocess compatibility - 2026-04-30

### Fixed

- MCP tools now return `Response` (text content with JSON-encoded payload) instead of `ResponseFactory` (structured content). This fixes a `BadMethodCallException: Method Laravel\Mcp\ResponseFactory::isError does not exist` raised when the tools are executed in a subprocess by `laravel/boost`'s `ExecuteToolCommand`, which assumes a `Response` return value and calls `->isError()` on it directly.

The payload is preserved — clients that read the `content[0].text` field can still `json_decode` it. The trade-off is the loss of the formal MCP `structuredContent` field in the JSON-RPC response, which is required until `laravel/boost` is patched upstream to handle `ResponseFactory` (see `vendor/laravel/boost/src/Console/ExecuteToolCommand.php`).

### Tests

- All 7 MCP tools updated and re-tested (`Response` instead of `ResponseFactory`, payload extracted via `json_decode((string) $response->content(), true)`)
- New `BoostExecuteToolCompatibilityTest` simulates the boost subprocess flow: invokes `handle()` then `isError()` on every tool

### Compatibility

- No breaking changes for direct MCP server usage
- The 7 tools introduced in v2.1.0 keep their schemas and parameter contracts

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

## v2.0.3 - 2026-04-10

### Fixed

- Allow nullable `value` property on `Translation` data class to match existing validator rule

## v2.0.2 — Laravel 11 container compatibility fix - 2026-03-19

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

## v2.0.0 — Laravel 12 & PHP 8.4 Support - 2026-03-18

> **Breaking change**: Laravel 10 is no longer supported. Please upgrade to Laravel 11 or 12.

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

**Full Changelog**: https://github.com/BrunosCode/laravel-translation-handler/compare/v1.0.0...v2.0.0

## v1.0.0 — First Stable Release - 2026-03-18

Laravel Translation Handler lets you move, import, and export translations across PHP files, JSON files, CSV files, and database — via artisan commands or a Facade API.

### Features

- **4 translation formats**: PHP files, JSON files, CSV files, database
- **Artisan commands**: `translation-handler`, `translation-handler:import`, `translation-handler:export`, `translation-handler:get`, `translation-handler:set`
- **`--guided`**: interactive mode, prompts for each option
- **`--fresh`**: delete existing translations before writing
- **`--force`**: overwrite existing translations
- **`--from-path` / `--to-path`**: custom paths per command
- **Facade API**: `import()`, `export()`, `get()`, `set()`, `delete()`
- **Nested JSON support** via `jsonNested` config option

### Requirements

| Laravel | PHP |
|---------|-----|
| 11.x | 8.2, 8.3 |
| 10.x | 8.2, 8.3 |

## v0.1.7 - 2025-07-08

- `fresh` command option

## v0.1.6 - 2025-03-01

- Fix documentation

## v0.1.5 - 2025-03-01

- Added documentation

## v0.1.4 - 2025-02-22

- `jsonFormat` and `jsonNested` options

## v0.1.3 - 2025-02-20

- Feature tests and fix commands

## v0.1.2 - 2025-02-14

- Removed Laravel 10 requirement

## v0.1.1 - 2025-02-13

- Resolved test errors and dependencies

## v0.1 - 2025-02-10

- First prerelease (still missing documentation; Windows tests not running)
