# Laravel Translation Handler

Manage translations in Laravel across PHP files, JSON files, CSV files, and database — and let an AI agent handle them for you via [Laravel Boost](https://github.com/laravel/boost) MCP tools.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/brunoscode/laravel-translation-handler.svg?style=flat-square)](https://packagist.org/packages/brunoscode/laravel-translation-handler)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/brunoscode/laravel-translation-handler/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/brunoscode/laravel-translation-handler/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/brunoscode/laravel-translation-handler/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/brunoscode/laravel-translation-handler/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/brunoscode/laravel-translation-handler.svg?style=flat-square)](https://packagist.org/packages/brunoscode/laravel-translation-handler)

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

---

## AI-Powered Translation Management with Laravel Boost

When [Laravel Boost](https://github.com/laravel/boost) is installed, this package automatically registers **7 MCP tools** into Boost's MCP server. This lets any MCP-compatible AI agent (Claude, Cursor, GitHub Copilot, etc.) read and write your translations directly — no manual commands, no copy-pasting.

### What the AI can do

- Browse your translation keys by group and depth level
- Look up a specific key in any locale
- Add or update a single translation in any format
- Add or update a key across **all locales at once**
- Sync translations between storage formats
- Read the full translation configuration

### Setup

Install Laravel Boost, then connect your AI assistant to its MCP server. No further configuration is required — the tools register automatically on package detection.

```bash
composer require laravel/boost
```

### Available MCP Tools

Format values for `format` / `from` / `to` parameters: `php_file`, `json_file`, `csv_file`, `db`.

#### `get-translation-config-tool` (read-only)

Returns the active configuration: locales, file names, key delimiter, default formats, and storage paths. Useful as a first call to understand the project's translation setup.

No parameters.

#### `list-translations-tool` (read-only)

Lists translations from a format with optional filters.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string | yes | Storage format to read from |
| `locale` | string | no | Filter by locale (e.g. `en`) |
| `group` | string | no | Filter by key prefix (e.g. `auth` returns all `auth.*` keys) |

#### `list-translation-groups-tool` (read-only)

Lists unique key groups at a given depth. Use this to explore the key hierarchy before reading or writing translations — especially useful in large projects.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string | yes | Storage format to read from |
| `level` | integer | no | Number of delimiters in the group name. `0` = top-level (e.g. `auth`), `1` = second-level (e.g. `auth.messages`). Defaults to `0`. |
| `search` | string | no | Case-insensitive filter on group names |

#### `find-translation-tool` (read-only)

Finds a specific translation by key and locale.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string | yes | Storage format to read from |
| `key` | string | yes | Dot-delimited key (e.g. `auth.welcome`) |
| `locale` | string | yes | Locale to look up |

#### `set-translation-tool` (write)

Sets or updates a single translation value.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string | yes | Storage format to write to |
| `key` | string | yes | Dot-delimited key |
| `locale` | string | yes | Target locale |
| `value` | string | yes | Translation value |
| `force` | boolean | no | Overwrite existing value (default `false`) |

#### `set-all-locales-translation-tool` (write)

Sets or updates a translation key for **all locales at once**. Ideal when the AI generates translations for every supported language in a single step.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string | yes | Storage format to write to |
| `key` | string | yes | Dot-delimited key |
| `values` | object | yes | Map of locale → value, e.g. `{"en": "Hello", "it": "Ciao"}` |
| `force` | boolean | no | Overwrite existing values (default `false`) |

#### `sync-translations-tool` (write)

Copies translations from one storage format to another.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `from` | string | yes | Source format |
| `to` | string | yes | Destination format (must differ from `from`) |
| `force` | boolean | no | Overwrite existing translations in destination (default `false`) |

### Recommended workflow for editing translations

**Use `db` as the working format for all writes, then sync to files at the end.**

File-based formats (PHP, JSON, CSV) rewrite the entire file on every write operation. The database format writes only the affected rows, making each change significantly faster. Once all edits are done, a single sync pushes everything to the target file format.

```
1. Read/browse   → db (or any format for read-only queries)
2. Write keys    → always db
3. Finalise      → sync-translations-tool  from: db  to: <target format>
```

When no database is configured, write directly to the file format but batch all locales for a key into a single `set-all-locales-translation-tool` call rather than one call per locale.

### Example AI workflow

> **You:** "Add a `auth.too_many_attempts` key in English and Italian to the JSON files."

The AI will:
1. Call `get-translation-config-tool` to confirm the locales and format
2. Call `set-all-locales-translation-tool` with `{"en": "Too many attempts. Please try again later.", "it": "Troppi tentativi. Riprova più tardi."}` targeting `json_file`

> **You:** "Migrate all translations from PHP files to the database."

The AI will call `sync-translations-tool` with `from: php_file`, `to: db`.

> **You:** "What translation groups exist at the top level?"

The AI will call `list-translation-groups-tool` with `format: php_file`, `level: 0`.

---

## Quick Start

```bash
# Import translations from PHP to JSON
php artisan translation-handler:import --from=php_file --to=json_file

# Export translations from JSON to PHP, overwriting existing
php artisan translation-handler:export --from=json_file --to=php_file --force

# Move translations interactively
php artisan translation-handler php_file json_file --guided

# Get a specific translation
php artisan translation-handler:get php_file test.welcome en

# Set a specific translation
php artisan translation-handler:set php_file test.welcome en "Welcome!"
```

Or use the Facade:

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

// Import PHP → JSON
TranslationHandler::import(TranslationOptions::PHP, TranslationOptions::JSON);

// Export JSON → PHP, overwriting existing
TranslationHandler::export(TranslationOptions::JSON, TranslationOptions::PHP, force: true);
```

## Commands

### Shared Options

These options are available on `translation-handler`, `translation-handler:import`, and `translation-handler:export`:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--force` | bool | `false` | Overwrite existing translations |
| `--fresh` | bool | `false` | Delete existing translations before writing |
| `--file-names` | array | config `fileNames` | Translation file names to process |
| `--locales` | array | config `locales` | Locales to process |
| `--from-path` | string | format default | Custom source path |
| `--to-path` | string | format default | Custom destination path |
| `--guided` | bool | `false` | Interactive mode, prompts for each option |

### `translation-handler`

Move translations from one format to another. Source and destination are positional arguments.

```bash
php artisan translation-handler {from?} {to?} [options]
```

If `from` or `to` are omitted, you will be prompted to choose.

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

### `translation-handler:get`

Get a single translation value.

```bash
php artisan translation-handler:get {from?} {key?} {locale?} {--from-path=}
```

### `translation-handler:set`

Set a single translation value.

```bash
php artisan translation-handler:set {to?} {key?} {locale?} {value?} {--to-path=} {--force}
```

## Facade API

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
```

### import / export

Both methods share the same signature:

```php
TranslationHandler::import(
    from: ?string,     // source format (default: config value)
    to: ?string,       // destination format (default: config value)
    force: bool,       // overwrite existing (default: false)
    fromPath: ?string, // custom source path (default: null)
    toPath: ?string,   // custom destination path (default: null)
): bool;

TranslationHandler::export(/* same signature */): bool;
```

### get

```php
$translations = TranslationHandler::get(
    from: string,      // source format
    path: ?string,     // custom path (default: null)
): TranslationCollection;
```

### set

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
| `phpFormat` | `false` | Format PHP output |
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

## Contributing

Contributions are welcome! Please submit a pull request or open an issue to discuss what you would like to change.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
