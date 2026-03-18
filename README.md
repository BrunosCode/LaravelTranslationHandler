# Laravel Translation Handler

Manage translations in Laravel across PHP files, JSON files, CSV files, and database.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/brunoscode/laravel-translation-handler.svg?style=flat-square)](https://packagist.org/packages/brunoscode/laravel-translation-handler)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/brunoscode/laravel-translation-handler/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/brunoscode/laravel-translation-handler/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/brunoscode/laravel-translation-handler/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/brunoscode/laravel-translation-handler/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/brunoscode/laravel-translation-handler.svg?style=flat-square)](https://packagist.org/packages/brunoscode/laravel-translation-handler)

## Supported Formats

| Format | Constant | Description |
|--------|----------|-------------|
| PHP | `TranslationOptions::PHP` | Standard Laravel PHP translation files |
| JSON | `TranslationOptions::JSON` | JSON translation files |
| CSV | `TranslationOptions::CSV` | CSV translation files |
| Database | `TranslationOptions::DB` | Database-backed translations |

## Installation

```bash
composer require brunoscode/laravel-translation-handler
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="BrunosCode\TranslationHandler\TranslationHandlerServiceProvider"
```

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
