---
name: translation-handler-development
description: Build and manage Laravel translations across PHP files, JSON files, CSV files, and database using the brunoscode/laravel-translation-handler package.
---

# Laravel Translation Handler Development

## When to use this skill

Use this skill when:
- Migrating translations between formats (PHP ↔ JSON ↔ CSV ↔ DB)
- Importing or exporting translation files programmatically
- Reading or writing individual translation keys
- Configuring multi-locale translation management
- Working with the `TranslationHandler` facade, `TranslationCollection`, or `Translation` data objects

## Installation

```bash
composer require brunoscode/laravel-translation-handler
php artisan translation-handler:install
```

For database-backed translations, also run:

```bash
php artisan migrate
```

## Core Concepts

### Formats

Four storage formats, referenced by constants on `TranslationOptions`:

| Constant | String value | Description |
|----------|-------------|-------------|
| `TranslationOptions::PHP` | `php_file` | Standard Laravel PHP translation arrays |
| `TranslationOptions::JSON` | `json_file` | JSON translation files |
| `TranslationOptions::CSV` | `csv_file` | CSV translation files |
| `TranslationOptions::DB` | `db` | Database-backed translations |

### Data Objects

**`Translation`** — a single translation entry:

```php
use BrunosCode\TranslationHandler\Data\Translation;

$t = new Translation(
    key: 'auth.welcome',  // dot-delimited key
    locale: 'en',
    value: 'Welcome!'     // nullable string
);
```

**`TranslationCollection`** — typed collection of `Translation` objects:

```php
use BrunosCode\TranslationHandler\Collections\TranslationCollection;

$collection = new TranslationCollection([$t]);
```

**`TranslationOptions`** — package configuration snapshot. Usually read from config; construct manually only when overriding defaults.

## Facade API

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
```

### import / export

```php
TranslationHandler::import(
    from: TranslationOptions::PHP,   // source format
    to: TranslationOptions::JSON,    // destination format
    force: false,                    // overwrite existing translations
    fromPath: null,                  // custom source path (null = config default)
    toPath: null,                    // custom destination path (null = config default)
): bool;

TranslationHandler::export(/* same signature */): bool;
```

### get — read all translations from a format

```php
$collection = TranslationHandler::get(
    from: TranslationOptions::PHP,
    path: null,
): TranslationCollection;
```

### set — write translations to a format

```php
$count = TranslationHandler::set(
    translations: $collection,
    to: TranslationOptions::JSON,
    path: null,
    force: false,
): int;
```

### delete — remove all translations from a format

```php
$count = TranslationHandler::delete(
    from: TranslationOptions::DB,
    path: null,
): int;
```

### Options management

```php
TranslationHandler::setOption('keyDelimiter', '_');
$value = TranslationHandler::getOption('keyDelimiter');
TranslationHandler::setOptions(new TranslationOptions([...]));
TranslationHandler::resetOptions();
```

## TranslationCollection API

```php
// Filter
$collection->whereLocale('en');
$collection->whereLocaleIn(['en', 'it']);
$collection->whereKey('auth.welcome');
$collection->whereKeyIn(['auth.welcome', 'auth.login']);
$collection->whereGroup('auth');              // keys starting with 'auth.'
$collection->whereGroupIn(['auth', 'validation']);
$collection->whereValue('Welcome!');
$collection->whereValueContains('Welcome');
$collection->whereValueIn(['Welcome!', 'Ciao!']);

// Add / replace
$collection->addTranslation($translation);        // skip if key+locale exists
$collection->replaceTranslation($translation);    // overwrite if key+locale exists
$collection->addTranslations($otherCollection);
$collection->replaceTranslations($otherCollection);

// Utilities
$collection->searchTranslation($translation);
$collection->sortTranslations();
$collection->clone();
```

## Artisan Commands

### Move translations between formats

```bash
php artisan translation-handler {from?} {to?} [options]

# Examples:
php artisan translation-handler php_file json_file
php artisan translation-handler php_file json_file --guided
```

### Import

```bash
php artisan translation-handler:import [options]
# Defaults to config defaultImportFrom → defaultImportTo
php artisan translation-handler:import --from=php_file --to=json_file --force
```

### Export

```bash
php artisan translation-handler:export [options]
# Defaults to config defaultExportFrom → defaultExportTo
php artisan translation-handler:export --from=json_file --to=php_file --force
```

### Get a single translation

```bash
php artisan translation-handler:get {from?} {key?} {locale?} {--from-path=}
php artisan translation-handler:get php_file auth.welcome en
```

### Set a single translation

```bash
php artisan translation-handler:set {to?} {key?} {locale?} {value?} {--to-path=} {--force}
php artisan translation-handler:set json_file auth.welcome en "Welcome!" --force
```

### Shared options

| Option | Description |
|--------|-------------|
| `--force` | Overwrite existing translations |
| `--fresh` | Delete all before writing |
| `--file-names=*` | Translation file names to process |
| `--locales=*` | Locales to process |
| `--from-path=` | Custom source path |
| `--to-path=` | Custom destination path |
| `--guided` | Interactive mode |

## Common Workflows

### Migrate PHP → JSON

```php
TranslationHandler::import(TranslationOptions::PHP, TranslationOptions::JSON, force: true);
```

### Read, modify, write back

```php
use BrunosCode\TranslationHandler\Data\Translation;

$collection = TranslationHandler::get(TranslationOptions::JSON);
$collection->addTranslation(new Translation('auth.welcome', 'it', 'Benvenuto!'));
TranslationHandler::set($collection, TranslationOptions::JSON, force: true);
```

### Sync DB from PHP files

```php
$translations = TranslationHandler::get(TranslationOptions::PHP);
TranslationHandler::set($translations, TranslationOptions::DB, force: true);
```

### Export a single locale to CSV

```php
$all = TranslationHandler::get(TranslationOptions::PHP);
$italian = $all->whereLocale('it');
TranslationHandler::set($italian, TranslationOptions::CSV, force: true);
```

### Merge translations from two sources

```php
$base = TranslationHandler::get(TranslationOptions::PHP);
$extra = TranslationHandler::get(TranslationOptions::DB);
$base->addTranslations($extra);  // DB keys not already in $base are added
TranslationHandler::set($base, TranslationOptions::JSON, force: true);
```

## MCP Tools (available when laravel/boost is installed)

The following tools are injected automatically into Boost's MCP server. No configuration required — they register on detection of `laravel/boost`.

Format values for `format` / `from` / `to` parameters: `php_file`, `json_file`, `csv_file`, `db`.

### `get-translation-config-tool` (read-only, idempotent)

Returns current config: locales, fileNames, key delimiter, default formats, and storage paths.

No parameters.

### `list-translations-tool` (read-only, idempotent)

Lists translations from a storage format with optional filters.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string (enum) | yes | One of the format values |
| `locale` | string | no | Filter by locale (e.g. `en`, `it`) |
| `group` | string | no | Filter by key prefix (e.g. `auth` matches `auth.*`) |

### `find-translation-tool` (read-only, idempotent)

Finds a specific translation by key + locale.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string (enum) | yes | One of the format values |
| `key` | string | yes | Dot-delimited translation key (e.g. `auth.welcome`) |
| `locale` | string | yes | Locale to look up |

### `set-translation-tool` (write)

Sets or updates a single translation value.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string (enum) | yes | One of the format values |
| `key` | string | yes | Dot-delimited translation key |
| `locale` | string | yes | Locale to write |
| `value` | string | yes | Translation value |
| `force` | boolean | no | Overwrite existing value (default `false`) |

### `sync-translations-tool` (write)

Copies translations from one storage format to another.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `from` | string (enum) | yes | Source format |
| `to` | string (enum) | yes | Destination format (must differ from `from`) |
| `force` | boolean | no | Overwrite existing translations in destination (default `false`) |

## Configuration (`config/translation-handler.php`)

Publish with `php artisan vendor:publish --provider="BrunosCode\TranslationHandler\TranslationHandlerServiceProvider"`.

Key options:

```php
return [
    'keyDelimiter'      => '.',                // delimiter in translation keys
    'fileNames'         => ['messages'],       // PHP/JSON/CSV file names to process
    'locales'           => ['en', 'it'],       // locales to process

    'defaultImportFrom' => 'php_file',
    'defaultImportTo'   => 'json_file',
    'defaultExportFrom' => 'json_file',
    'defaultExportTo'   => 'php_file',

    // PHP files
    'phpPath'           => lang_path(),
    'phpFormat'         => false,

    // JSON files
    'jsonPath'          => lang_path(),
    'jsonFileName'      => '',                 // '' = locale as filename; set = locale as folder
    'jsonNested'        => false,
    'jsonFormat'        => true,

    // CSV files
    'csvPath'           => storage_path('lang'),
    'csvFileName'       => 'translations',
    'csvDelimiter'      => ';',               // must differ from keyDelimiter

    // Database (run migrations first)
    'dbHandlerClass'    => \BrunosCode\TranslationHandler\DatabaseHandler::class,
];
```
