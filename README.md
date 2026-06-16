# Laravel Translation Handler

> Manage Laravel translations across PHP, JSON, CSV, and the database — keep the formats in sync, edit them live in production, and let an AI agent handle them via Laravel Boost.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/brunoscode/laravel-translation-handler.svg?style=flat-square)](https://packagist.org/packages/brunoscode/laravel-translation-handler)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/brunoscode/laravel-translation-handler/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/brunoscode/laravel-translation-handler/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/brunoscode/laravel-translation-handler/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/brunoscode/laravel-translation-handler/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/brunoscode/laravel-translation-handler.svg?style=flat-square)](https://packagist.org/packages/brunoscode/laravel-translation-handler)
[![License](https://img.shields.io/packagist/l/brunoscode/laravel-translation-handler.svg?style=flat-square)](LICENSE.md)

Laravel translations end up split across `lang/*.php` files and a JSON copy the frontend consumes — two sources that drift apart, and neither editable in production without a redeploy. This package treats PHP files, JSON files, CSV files, and the database as interchangeable views of one set of translations. Two workflows it was built around:

- **Client-managed translations on staging/production.** Store translations in the `db` format so they can be edited in the running environment — by the client, without touching files or redeploying — then synced back to files.
- **PHP files as the single source, frontend generated from them (or the reverse).** Keep `php_file` as the source of truth and generate the JSON the frontend consumes — or go the other way (`json_file` → `php_file`). Edit one side, regenerate the other.

```
                    sync · import · export
        ┌───────────┬───────────┬───────────┬───────────┐
        │  php_file │ json_file │  csv_file │     db    │
        └───────────┴───────────┴───────────┴───────────┘
              translations move between any two formats
```

## Table of Contents

- [Requirements](#requirements)
- [Supported Formats](#supported-formats)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Commands](#commands)
- [Facade API](#facade-api)
- [Linter-friendly Writes](#linter-friendly-writes)
- [AI Translation Management with Laravel Boost](#ai-translation-management-with-laravel-boost)
- [Configuration](#configuration)
- [Testing](#testing)
- [Changelog](#changelog)
- [Credits](#credits)
- [Contributing](#contributing)
- [License](#license)

## Requirements

| Laravel | PHP |
|---------|-----|
| 12.x | 8.2, 8.3, 8.4 |
| 11.x | 8.2, 8.3, 8.4 |

## Supported Formats

| Format | Constant | Description |
|--------|----------|-------------|
| php_file | `TranslationOptions::PHP` | Standard Laravel PHP translation files |
| json_file | `TranslationOptions::JSON` | JSON translation files |
| csv_file | `TranslationOptions::CSV` | CSV translation files |
| db | `TranslationOptions::DB` | Database-backed translations |

## Installation

```bash
composer require brunoscode/laravel-translation-handler
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="BrunosCode\TranslationHandler\TranslationHandlerServiceProvider"
```

For database-backed translations, run the migrations:

```bash
php artisan migrate
```

## Quick Start

```bash
# Sync translations between formats
php artisan translation-handler:sync php_file json_file

# Import / export using config defaults
php artisan translation-handler:import
php artisan translation-handler:export --force

# List all translations from PHP files
php artisan translation-handler:list php_file

# List translations filtered by locale and group
php artisan translation-handler:list php_file --locale=en --group=auth

# List top-level key groups
php artisan translation-handler:list-groups php_file

# List second-level groups, filtered by search
php artisan translation-handler:list-groups php_file --level=1 --search=messages

# Find a specific translation
php artisan translation-handler:find php_file auth.welcome en

# Get the raw value of a translation
php artisan translation-handler:get php_file auth.welcome en

# Set a specific translation
php artisan translation-handler:set php_file auth.welcome en "Welcome!"

# Delete a translation key (all locales)
php artisan translation-handler:delete php_file auth.welcome

# Delete a translation key for a specific locale
php artisan translation-handler:delete php_file auth.welcome --locale=en

# Delete all keys under a group
php artisan translation-handler:delete-group php_file auth

# Sort all keys alphabetically
php artisan translation-handler:sort php_file

# Sort only specific locales and groups
php artisan translation-handler:sort php_file --locale=en --group=auth

# Check source code for missing (or orphan) translation keys
php artisan translation-handler:check php_file --show-keys --orphans
```

Or use the Facade:

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

// Copy PHP → JSON (explicit formats)
TranslationHandler::sync(TranslationOptions::PHP, TranslationOptions::JSON);

// Copy JSON → PHP, overwriting existing
TranslationHandler::sync(TranslationOptions::JSON, TranslationOptions::PHP, force: true);

// Import using config defaults
TranslationHandler::import();

// Export using config defaults
TranslationHandler::export();
```

## Commands

### Shared Options

These options are available on `translation-handler:sync`, `translation-handler:import`, and `translation-handler:export`:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--force` | bool | `false` | Overwrite values that already exist in the destination (see conflict behavior below) |
| `--fresh` | bool | `false` | Delete existing translations before writing |
| `--file-names` | array | config `fileNames` | Translation file names to process |
| `--locales` | array | config `locales` | Locales to process |
| `--from-path` | string | format default | Custom source path |
| `--to-path` | string | format default | Custom destination path |
| `--guided` | bool | `false` | Interactive mode, prompts for each option |

### `translation-handler:sync`

Sync translations from one format to another. Source and destination are positional arguments.

```bash
php artisan translation-handler:sync {from?} {to?} [options]
```

If `from` or `to` are omitted, you will be prompted to choose.

**Conflict behavior.** By default a sync is a **non-destructive merge**: keys missing in the destination are added; keys that already exist keep their current destination value (the source value is ignored). It never fails or prompts on a conflict. Pass `--force` to overwrite existing values with the source, or `--fresh` to wipe the destination before writing. The same applies to `import` and `export`.

### `translation-handler` *(deprecated)*

> **Deprecated.** Use `translation-handler:sync` instead.

```bash
php artisan translation-handler {from?} {to?} [options]
```

### `translation-handler:import`

Import translations. Source and destination are passed via `--from` and `--to` options, defaulting to config values (`defaultImportFrom`, `defaultImportTo`).

```bash
php artisan translation-handler:import [options]
```

### `translation-handler:export`

Export translations. Source and destination are passed via `--from` and `--to` options, defaulting to config values (`defaultExportFrom`, `defaultExportTo`).

```bash
php artisan translation-handler:export [options]
```

### `translation-handler:list`

List translations from a storage format. Optionally filter by locale or key group prefix.

```bash
php artisan translation-handler:list {from?} {--from-path=} {--locale=} {--group=}
```

| Option | Description |
|--------|-------------|
| `--locale` | Filter by locale (e.g. `en`) |
| `--group` | Filter by key group prefix (e.g. `auth` returns all `auth.*` keys) |

### `translation-handler:list-groups`

List unique translation key groups from a storage format. A group is a key prefix at a given depth level. Optionally filter by search string.

```bash
php artisan translation-handler:list-groups {from?} {--from-path=} {--level=0} {--search=}
```

| Option | Description |
|--------|-------------|
| `--level` | Number of delimiters in the group name. `0` = top-level (e.g. `auth`), `1` = second-level (e.g. `auth.messages`). Defaults to `0`. |
| `--search` | Case-insensitive filter on group names |

### `translation-handler:find`

Find a specific translation by key and locale. Outputs a table with format, key, locale, and value.

```bash
php artisan translation-handler:find {from?} {key?} {locale?} {--from-path=}
```

### `translation-handler:get`

Get the raw value of a single translation.

```bash
php artisan translation-handler:get {from?} {key?} {locale?} {--from-path=}
```

### `translation-handler:set`

Set a single translation value.

```bash
php artisan translation-handler:set {to?} {key?} {locale?} {value?} {--to-path=} {--force}
```

### `translation-handler:delete`

Delete a translation key from a storage format. Omit `--locale` to delete all locales for the key.

```bash
php artisan translation-handler:delete {from?} {key?} {--locale=} {--from-path=}
```

### `translation-handler:delete-group`

Delete all translation keys under a group prefix from a storage format.

```bash
php artisan translation-handler:delete-group {from?} {group?} {--from-path=}
```

### `translation-handler:sort`

Sort translation keys alphabetically. Works on `php_file`, `json_file`, and `csv_file` only.

```bash
php artisan translation-handler:sort {from?} {--from-path=} {--locale=*} {--group=*}
```

| Option | Description |
|--------|-------------|
| `--locale` | Restrict sorting to this locale (repeatable: `--locale=en --locale=it`) |
| `--group` | Restrict sorting to this key group prefix (repeatable) |

### `translation-handler:check`

Scan the application's backend PHP and frontend JS/TS source for translation usages (`__()`, `trans()`, `trans_choice()`, `Lang::get()`, `@lang`, and `t()` / `i18next.t()`) and report keys that are referenced in code but not defined per locale. A non-zero exit code is returned when any key is missing, so it doubles as a CI gate.

```bash
php artisan translation-handler:check {from?} {--from-path=} {--locale=*} {--side=} {--show-keys} {--orphans}
```

| Option | Description |
|--------|-------------|
| `--locale` | Restrict the report to one or more locales (repeatable). Defaults to the configured `locales`. |
| `--side` | Limit scanning to a single configured side (`backend` / `frontend` by default). Defaults to all sides. |
| `--show-keys` | Print each missing (or orphan) key. |
| `--orphans` | Also list keys that are defined but never referenced in code (informational — does not affect the exit code). |

Defined keys are read from the chosen `from` format and scoped to the configured `fileNames`. The directories and extensions scanned per side are configurable via the `check` config entry:

```php
'check' => [
    'backend' => [
        'paths' => ['app', 'resources/views', 'routes', 'database'],
        'extensions' => ['php'],
    ],
    'frontend' => [
        'paths' => ['resources/js'],
        'extensions' => ['ts', 'tsx', 'js', 'jsx'],
    ],
],
```

Because defined keys are scoped to the configured `fileNames`, the check reflects exactly the translations the package manages. References to keys outside those groups (e.g. Laravel's own `auth.*` / `validation.*`) are reported as missing unless you add their groups to `fileNames` — or set [`checkIncludeFrameworkKeys`](#check) to `true` to treat Laravel's bundled lang keys as defined.

## Facade API

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
```

### sync

Copies translations from one format to another. Unlike `import`/`export`, `from` and `to` are required — no config defaults are used.

With `force: false` (default) this is a **non-destructive merge** — keys missing in the destination are added, existing keys keep their value. With `force: true` existing values are overwritten by the source.

Returns `false` if the source has no translations to read; otherwise returns the number of translations changed in the destination (`0` means already in sync).

```php
TranslationHandler::sync(
    from: string,      // source format
    to: string,        // destination format
    force: bool,       // overwrite existing (default: false)
    fromPath: ?string, // custom source path (default: null)
    toPath: ?string,   // custom destination path (default: null)
): false|int;
```

### import / export

Both methods share the same signature and the same return semantics as `sync`. `from` and `to` fall back to config defaults (`defaultImportFrom`/`defaultImportTo` and `defaultExportFrom`/`defaultExportTo`) when omitted.

```php
TranslationHandler::import(
    from: ?string,     // source format (default: config value)
    to: ?string,       // destination format (default: config value)
    force: bool,       // overwrite existing (default: false)
    fromPath: ?string, // custom source path (default: null)
    toPath: ?string,   // custom destination path (default: null)
): false|int;

TranslationHandler::export(/* same signature */): false|int;
```

### find

Finds a single translation by key and locale. Returns `null` if not found.

```php
$translation = TranslationHandler::find(
    from: string,      // source format
    key: string,       // dot-delimited key
    locale: string,    // locale to look up
    path: ?string,     // custom path (default: null)
): ?Translation;
```

### listTranslations

Returns a filtered `TranslationCollection`. Applies locale and/or group prefix filters on top of `get()`.

```php
$translations = TranslationHandler::listTranslations(
    from: string,      // source format
    path: ?string,     // custom path (default: null)
    locale: ?string,   // filter by locale (default: null = all)
    group: ?string,    // filter by key group prefix (default: null = all)
): TranslationCollection;
```

### listGroups

Returns a sorted `Collection` of unique key group names at a given depth level.

```php
$groups = TranslationHandler::listGroups(
    from: string,      // source format
    path: ?string,     // custom path (default: null)
    level: int,        // 0 = top-level groups, 1 = second-level, … (default: 0)
    search: ?string,   // case-insensitive filter on group names (default: null)
): Collection;
```

### get

```php
$translations = TranslationHandler::get(
    from: string,      // source format
    path: ?string,     // custom path (default: null)
): TranslationCollection;
```

### set

Returns the number of translations actually changed (added, modified, or removed) in the destination — `0` if the destination already matches the desired state.

```php
$count = TranslationHandler::set(
    translations: TranslationCollection,
    to: string,        // destination format
    path: ?string,     // custom path (default: null)
    force: bool,       // overwrite existing (default: false)
): int;
```

Example:

```php
use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

$translation = new Translation('welcome', 'en', 'Welcome!');
$collection = new TranslationCollection([$translation]);

TranslationHandler::set($collection, TranslationOptions::JSON);
```

### deleteKey

Deletes a specific translation key from a format. Pass `locale` to target a single locale; omit to delete all locales.

```php
$count = TranslationHandler::deleteKey(
    from: string,      // format to delete from
    key: string,       // dot-delimited key
    locale: ?string,   // locale to delete (default: null = all locales)
    path: ?string,     // custom path (default: null)
): int;
```

### deleteGroup

Deletes all keys whose name starts with the given group prefix.

```php
$count = TranslationHandler::deleteGroup(
    from: string,      // format to delete from
    group: string,     // group prefix (e.g. "auth" removes all "auth.*" keys)
    path: ?string,     // custom path (default: null)
): int;
```

### sortKeys

Sorts translation keys alphabetically within a format. Supports `php_file`, `json_file`, and `csv_file`. Optionally restrict by locale and/or group.

```php
$count = TranslationHandler::sortKeys(
    from: string,      // format to sort (php_file, json_file, csv_file)
    locales: array,    // restrict to these locales (default: [] = all)
    groups: array,     // restrict to these group prefixes (default: [] = all)
    path: ?string,     // custom path (default: null)
): int;
```

### delete

```php
$count = TranslationHandler::delete(
    from: string,      // format to delete from
    path: ?string,     // custom path (default: null)
): int;
```

### Options Management

```php
// Get/set individual options
TranslationHandler::setOption('keyDelimiter', '_');
$value = TranslationHandler::getOption('keyDelimiter');

// Replace all options
TranslationHandler::setOptions(new TranslationOptions([...]));

// Reset to defaults
TranslationHandler::resetOptions();
```

## Linter-friendly Writes

File-based formats (PHP, JSON, CSV) skip the write entirely when the resulting content would be identical to what's already on disk — including key order. This avoids churn from your code formatter (Pint, PHP-CS-Fixer, Prettier, …): a file already formatted by the linter is left untouched as long as its translations and ordering match the new state.

- Comparison is **strict and includes key order** — a re-sort still triggers a rewrite (intended, since `sort` is an explicit operation).
- Counts returned by `set`, `sync`, `import`, `export` reflect the number of translations actually **changed** (added, modified, removed), not the total in the collection. A no-op write returns `0`.
- `sync` / `import` / `export` return `false` only when there are no source translations to read; `0` is a successful no-op.

### Formatting PHP files with Pint

Set `phpPint` to `true` to run [Pint](https://github.com/laravel/pint) on the PHP files written during a write operation, so generated translation files match your project's own code style and stay diff-stable across runs. The binary is resolved from your project first (`vendor/bin/pint`), then from this package's own vendor (only when developing the package itself). If no Pint binary is found the step is skipped silently and the files keep their raw `phpFormat` output — so enabling `phpPint` is a no-op unless `laravel/pint` is installed.

## AI Translation Management with Laravel Boost

When [Laravel Boost](https://github.com/laravel/boost) is installed, this package auto-registers **11 MCP tools** into Boost's MCP server — no configuration. Any MCP-compatible agent (Claude, Cursor, GitHub Copilot, …) can then browse, add, update, translate, sync, delete, sort, and audit your translations directly.

```bash
composer require laravel/boost
```

Two Boost skills ship with the package and guide the agent — the README only sketches the tools; the skills (and the MCP server's own schemas) hold the detail:

- **`translation-handler-mcp`** — the agent workflow: the recommended `db`-then-`sync` pattern, group and all-locale writes, and missing-key checks.
- **`translation-handler-development`** — writing custom PHP against the package (facade, collection, extending handlers / the checker).

### Tools at a glance

All formats accept `php_file`, `json_file`, `csv_file`, `db`.

- **Read** — `get-translation-config-tool` (locales, paths, defaults), `list-translation-groups-tool` (browse keys by depth), `list-translations-tool` (filter by locale/group), `find-translation-tool` (one key + locale).
- **Write** — `set-translation-tool` (one key + locale), `set-all-locales-translation-tool` (one key, every locale), `set-translation-group-tool` (a whole group in one call), `sync-translations-tool` (copy between formats), `delete-translation-tool`, `delete-translation-group-tool`.
- **Maintenance** — `sort-translations-tool` (alphabetical; file formats only), `check-translations-tool` (keys used in code but missing per locale).

### Recommended workflow for editing translations

**Use `db` as the working format for all writes, then sync to files at the end.**

File-based formats (PHP, JSON, CSV) rewrite the entire file on every write operation. The database format writes only the affected rows, making each change significantly faster. Once all edits are done, a single sync pushes everything to the target file format.

```
1. Read/browse   → db (or any format for read-only queries)
2. Write keys    → always db
3. Finalise      → sync-translations-tool  from: db  to: <target format>
4. Verify        → check-translations-tool  (or translation-handler:check)
```

When no database is configured, write directly to the file format but batch all locales for a key into a single `set-all-locales-translation-tool` call rather than one call per locale. End the work with a `check` run to confirm no referenced key is missing.

### Example AI workflow

> **You:** "Add a `auth.too_many_attempts` key in English and Italian to the JSON files."

The AI will:
1. Call `get-translation-config-tool` to confirm the locales and format
2. Call `set-all-locales-translation-tool` with `{"en": "Too many attempts. Please try again later.", "it": "Troppi tentativi. Riprova più tardi."}` targeting `json_file`

> **You:** "Translate the entire `auth` group into English, Italian, and Spanish."

The AI will call `set-translation-group-tool` with `format: db`, `group: auth`, and a `translations` object containing every auth subkey mapped to all three locales — in a single DB transaction — then sync `db` → `json_file`.

## Configuration

The `config/translation-handler.php` file contains:

### General

| Option | Default | Description |
|--------|---------|-------------|
| `keyDelimiter` | `.` | Delimiter used in translation keys |
| `fileNames` | `['translation-handler']` | Translation file names to process |
| `locales` | `['en']` | Supported locales |

### Default Formats

| Option | Default | Description |
|--------|---------|-------------|
| `defaultImportFrom` | `php_file` | Default source format for import |
| `defaultImportTo` | `json_file` | Default destination format for import |
| `defaultExportFrom` | `json_file` | Default source format for export |
| `defaultExportTo` | `php_file` | Default destination format for export |

### PHP

| Option | Default | Description |
|--------|---------|-------------|
| `phpPath` | `lang_path()` | Path to PHP translation files |
| `phpFormat` | `false` | Convert exported arrays to short syntax (`[]` instead of `array()`) |
| `phpPint` | `false` | Format exported PHP files with Pint after writing (see [Linter-friendly Writes](#linter-friendly-writes)) |
| `phpHandlerClass` | `PhpFileHandler::class` | Handler class |

### JSON

| Option | Default | Description |
|--------|---------|-------------|
| `jsonPath` | `lang_path()` | Path to JSON translation files |
| `jsonFileName` | `''` | File name (empty = use locale as filename, set = use locale as folder) |
| `jsonNested` | `false` | Nest output like PHP files |
| `jsonFormat` | `true` | Pretty-print JSON output |
| `jsonHandlerClass` | `JsonFileHandler::class` | Handler class |

### CSV

| Option | Default | Description |
|--------|---------|-------------|
| `csvPath` | `storage_path('lang')` | Path to CSV files |
| `csvFileName` | `translations` | CSV file name |
| `csvDelimiter` | `;` | CSV delimiter (must differ from `keyDelimiter`) |
| `csvHandlerClass` | `CsvFileHandler::class` | Handler class |

### Database

| Option | Default | Description |
|--------|---------|-------------|
| `dbHandlerClass` | `DatabaseHandler::class` | Handler class |

### Check

Used by `translation-handler:check` and `check-translations-tool` to locate source files. Paths are relative to the application base path (absolute paths allowed).

The keys of `check` define the **sides** to scan — `backend` and `frontend` by default, but you can rename, remove, or add sides freely. Each side must provide a `paths` array and an `extensions` array (the structure is validated).

Each side may also declare a `patterns` entry to override the extraction regexes — `static` patterns must capture a full key in group 1, `dynamic` patterns a key prefix. Every pattern is validated to be a compilable regex. When omitted, the bundled defaults are used (PHP patterns for the `backend` side, JS/TS patterns for any other side). For example, to recognise a custom helper:

```php
'check' => [
    'backend' => [
        'paths' => ['app', 'resources/views'],
        'extensions' => ['php'],
        'patterns' => [
            'static' => ["/(?:__|myTrans)\\(\\s*'([^'\\\\]+)'/"],
            'dynamic' => [],
        ],
    ],
],
```

Set `checkIncludeFrameworkKeys` to `true` to treat the keys shipped with Laravel's own bundled lang files (`auth`, `pagination`, `passwords`, `validation`) as defined. Laravel's translator falls back to those files even when your project never publishes them, so references like `__('auth.failed')` are valid at runtime; with this option enabled they are no longer reported as missing. The keys are read straight from `vendor/laravel/framework` (the bundled `en` locale) and flattened to your `keyDelimiter`, independent of `fileNames` — so it covers framework groups you don't publish too. It removes the need for a custom `checkerClass` subclass just to whitelist framework keys.

For customisation that can't be expressed as static patterns (programmatic generation, custom defined-key resolution), extend `TranslationChecker` and override `patternsFor()`, then point `checkerClass` at your subclass.

| Option | Default | Description |
|--------|---------|-------------|
| `check.backend.paths` | `['app', 'resources/views', 'routes', 'database']` | Backend directories to scan |
| `check.backend.extensions` | `['php']` | Backend file extensions |
| `check.frontend.paths` | `['resources/js']` | Frontend directories to scan |
| `check.frontend.extensions` | `['ts', 'tsx', 'js', 'jsx']` | Frontend file extensions |
| `checkIncludeFrameworkKeys` | `false` | Count Laravel's bundled lang keys (`auth`, `pagination`, `passwords`, `validation`) as defined, so the framework's own fallback keys are not reported as missing |
| `checkerClass` | `TranslationChecker::class` | Checker implementation. Extend `TranslationChecker` and override `patternsFor()` to customise the scanning regexes (e.g. for custom helpers or frameworks). |

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for what has changed recently.

## Credits

- [BrunosCode](https://github.com/BrunosCode)
- [All Contributors](https://github.com/BrunosCode/LaravelTranslationHandler/graphs/contributors)

## Contributing

Contributions are welcome! Please submit a pull request or open an issue to discuss what you would like to change.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
