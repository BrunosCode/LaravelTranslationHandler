# Laravel Translation Handler

Laravel Translation Handler is a package to manage translations in Laravel applications. It supports importing, exporting, and managing translations across different formats such as PHP files, CSV files, JSON files, and databases.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/brunoscode/laravel-translation-handler.svg?style=flat-square)](https://packagist.org/packages/brunoscode/laravel-translation-handler)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/brunoscode/laravel-translation-handler/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/brunoscode/laravel-translation-handler/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/brunoscode/laravel-translation-handler/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/brunoscode/laravel-translation-handler/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/brunoscode/laravel-translation-handler.svg?style=flat-square)](https://packagist.org/packages/brunoscode/laravel-translation-handler)

## Installation

You can install the package via composer:

```bash
composer require brunoscode/laravel-translation-handler
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="BrunosCode\TranslationHandler\TranslationHandlerServiceProvider"
```

This will create a `translation-handler.php` file in your `config` directory.

### Configuration Options

- `keyDelimiter`: The delimiter used in translation keys (default: `.`).
- `fileNames`: An array of translation file names (default: `['translation-handler']`).
- `locales`: An array of supported locales (default: `['en']`).
- `defaultImportFrom`: The default format to import translations from (default: `TranslationOptions::PHP`).
- `defaultImportTo`: The default format to import translations to (default: `TranslationOptions::PHP`).
- `defaultExportFrom`: The default format to export translations from (default: `TranslationOptions::PHP`).
- `defaultExportTo`: The default format to export translations to (default: `TranslationOptions::PHP`).
- `phpHandlerClass`: The handler class for PHP files (default: `BrunosCode\TranslationHandler\PhpFileHandler::class`).
- `csvHandlerClass`: The handler class for CSV files (default: `BrunosCode\TranslationHandler\CsvFileHandler::class`).
- `jsonHandlerClass`: The handler class for JSON files (default: `BrunosCode\TranslationHandler\JsonFileHandler::class`).
- `dbHandlerClass`: The handler class for database (default: `BrunosCode\TranslationHandler\DatabaseHandler::class`).
- `phpFormat`: Whether to format PHP translations (default: `false`).
- `phpPath`: The path to PHP translation files (default: `lang_path()`).
- `csvDelimiter`: The delimiter used in CSV files (default: `;`).
- `csvFileName`: The name of the CSV file (default: `translations`).
- `csvPath`: The path to CSV files (default: `storage_path('lang')`).
- `jsonPath`: The path to JSON files (default: `lang_path()`).
- `jsonFileName`: The name of the JSON file (default: `''`).
- `jsonNested`: Whether JSON output should be nested like PHP files (default: `false`).
- `jsonFormat`: Whether JSON output should be formatted (default: `true`).

## Commands

The package provides several Artisan commands to manage translations:

### `translation-handler`

Move translations from one format to another.

#### Usage:

```bash
php artisan translation-handler {from?} {to?} {--force} {--file-names=*} {--locales=*} {--from-path} {--to-path} {--guided}
```

#### Parameters:
- `from` (string|null): The format to move translations from. If not provided, you will be prompted to enter it.
- `to` (string|null): The format to move translations to. If not provided, you will be prompted to enter it.

#### Options:
- `--force` (bool): Whether to force the move, overwriting existing translations. Default is `false`.
- `--file-names` (array): An array of translation file names. Default is `fileNames` option.
- `--locales` (array): An array of supported locales. Default is `locales` option.
- `--from-path` (string|null): The path to the source translations. Default is the default path for the choose format.
- `--to-path` (string|null): The path to the destination translations. Default is the default path for the choose format.
- `--guided` (bool): Whether to enable guided mode. Default is `false`.

### `translation-handler:import`

Import translations from one format to another.

#### Usage:

```bash
php artisan translation-handler:import {--force} {--from} {--from-path} {--to} {--to-path} {--file-names=*} {--locales=*} {--guided}
```

#### Options:
- `--force` (bool): Whether to force the import, overwriting existing translations. Default is `false`.
- `--from` (string|null): The format to import translations from. Default is `defaultImportFrom` option.
- `--from-path` (string|null): The path to the source translations. Default is the default path for the choose format.
- `--to` (string|null): The format to import translations to. Default is `defaultImportTo` option.
- `--to-path` (string|null): The path to the destination translations. Default is the default path for the choose format.
- `--file-names` (array): An array of translation file names. Default is `fileNames` option.
- `--locales` (array): An array of supported locales. Default is `locales` option.
- `--guided` (bool): Whether to enable guided mode. Default is `false`.

### `translation-handler:export`

Export translations from one format to another.

#### Usage:

```bash
php artisan translation-handler:export {--force} {--from} {--from-path} {--file-names=*} {--locales=*} {--to} {--to-path} {--guided}
```

#### Options:
- `--force` (bool): Whether to force the export, overwriting existing translations. Default is `false`.
- `--from` (string|null): The format to export translations from. Default is `defaultExportFrom` option.
- `--from-path` (string|null): The path to the source translations. Default is the default path for the choose format.
- `--to` (string|null): The format to export translations to. Default is `defaultExportTo` option.
- `--to-path` (string|null): The path to the destination translations. Default is the default path for the choose format.
- `--file-names` (array): An array of translation file names. Default is `fileNames` option.
- `--locales` (array): An array of supported locales. Default is `locales` option.
- `--guided` (bool): Whether to enable guided mode. Default is `false`.

### `translation-handler:get`

Get a specific translation.

#### Usage:

```bash
php artisan translation-handler:get {from?} {key?} {locale?} {--from-path=}
```

#### Parameters:
- `from` (string|null): The format to get translations from. If not provided, you will be prompted to enter it.
- `key` (string|null): The translation key. If not provided, you will be prompted to enter it.
- `locale` (string|null): The translation locale. If not provided, you will be prompted to enter it.

#### Options:
- `--from-path` (string|null): The path to the source translations. Default is the default path for the choose format

### `translation-handler:set`

Set a specific translation.

#### Usage:

```bash
php artisan translation-handler:set {to?} {key?} {locale?} {value?} {--to-path=} {--force}
```

#### Parameters:
- `to` (string|null): The format to set translations to. If not provided, you will be prompted to enter it.
- `key` (string|null): The translation key. If not provided, you will be prompted to enter it.
- `locale` (string|null): The translation locale. If not provided, you will be prompted to enter it.
- `value` (string|null): The translation value. If not provided, you will be prompted to enter it.

#### Options:
- `--to-path` (string|null): The path to the destination translations. Default is the default path for the choose format.
- `--force` (bool): Whether to force the set, overwriting existing translations. Default is `false`.

## Facade

### Import Translations

To import translations from one format to another:

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

// Import translations from PHP files to JSON files
TranslationHandler::import(TranslationOptions::PHP, TranslationOptions::JSON);

// Import translations from CSV files to database
TranslationHandler::import(TranslationOptions::CSV, TranslationOptions::DB);
```

#### Parameters:
- `from` (string|null): The format to import translations from. Default is `defaultImportFrom` option.
- `to` (string|null): The format to import translations to. Default is `defaultImportTo` option.
- `force` (bool): Whether to force the import, overwriting existing translations. Default is `false`.
- `fromPath` (string|null): The path to the source translations. Default is the default path for the choose format.
- `toPath` (string|null): The path to the destination translations. Default is the default path for the choose format.

### Export Translations

To export translations from one format to another:

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

// Export translations from JSON files to PHP files
TranslationHandler::export(TranslationOptions::JSON, TranslationOptions::PHP);

// Export translations from database to CSV files
TranslationHandler::export(TranslationOptions::DB, TranslationOptions::CSV);
```

#### Parameters:
- `from` (string|null): The format to export translations from. Default is `defaultExportFrom` option.
- `to` (string|null): The format to export translations to. Default is `defaultExportTo` option.
- `force` (bool): Whether to force the export, overwriting existing translations. Default is `false`.
- `fromPath` (string|null): The path to the source translations. Default is the default path for the choose format.
- `toPath` (string|null): The path to the destination translations. Default is the default path for the choose format.

### Get Translations

To get translations from a specific format:

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

// Get translations from PHP files
$translations = TranslationHandler::get(TranslationOptions::PHP);

// Get translations from JSON files
$translations = TranslationHandler::get(TranslationOptions::JSON);
```

#### Parameters:
- `from` (string): The format to get translations from.
- `path` (string|null): The path to the source translations. Default is the default path for the choose format.

### Set Translations

To set translations to a specific format:

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

// Create a new translation
$translation = new Translation('key', 'en', 'value');

// Add the translation to a collection
$collection = new TranslationCollection([$translation]);

// Set translations to JSON files
TranslationHandler::set($collection, TranslationOptions::JSON);

// Set translations to database
TranslationHandler::set($collection, TranslationOptions::DB);
```

#### Parameters:
- `translations` (TranslationCollection): The collection of translations to set.
- `to` (string): The format to set translations to.
- `path` (string|null): The path to the destination translations. Default is the default path for the choose format.
- `force` (bool): Whether to force the set, overwriting existing translations. Default is `false`.

### Delete Translations

To delete translations from a specific format:

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

// Delete translations from PHP files
TranslationHandler::delete(TranslationOptions::PHP);

// Delete translations from CSV files
TranslationHandler::delete(TranslationOptions::CSV);
```

#### Parameters:
- `from` (string): The format to delete translations from.
- `path` (string|null): The path to the source translations. Default is the default path for the choose format.

### Advanced Usage

#### Setting Options

You can set specific options for the `TranslationHandler`:

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

// Set a specific option
TranslationHandler::setOption('keyDelimiter', '_');

// Set multiple options
$options = new TranslationOptions(array_merge(
    config('translation-handler'), 
    ['keyDelimiter' => '_',]
));
TranslationHandler::setOptions($options);
```

#### Parameters:
- `name` (string): The name of the option to set.
- `value` (mixed): The value of the option to set.

#### Resetting Options

To reset options to their default values:

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;

// Reset all options to default
TranslationHandler::resetOptions();
```

#### Getting Default Options

To get the default options:

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;

// Get default options
$defaultOptions = TranslationHandler::getDefaultOptions();
```

## Contributing

Contributions are welcome! Please submit a pull request or open an issue to discuss what you would like to change.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
