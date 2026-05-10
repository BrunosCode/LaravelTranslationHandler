---
name: translation-handler-development
description: Build and manage Laravel translations across PHP files, JSON files, CSV files, and database using the brunoscode/laravel-translation-handler package.
---

# Laravel Translation Handler Development

## When to use this skill

Use this skill when:
- Migrating translations between formats (PHP ↔ JSON ↔ CSV ↔ DB)
- Importing or exporting translation files programmatically
- Reading, writing, deleting, or sorting individual translation keys or groups
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

### sync — copy translations from one format to another

```php
TranslationHandler::sync(
    from: TranslationOptions::PHP,
    to: TranslationOptions::DB,
    force: false,
    fromPath: null,
    toPath: null,
): bool;
```

### find — look up a single key+locale

```php
$translation = TranslationHandler::find(
    from: TranslationOptions::PHP,
    key: 'auth.welcome',
    locale: 'en',
    path: null,
): ?Translation;
```

### listTranslations — filtered collection

```php
$translations = TranslationHandler::listTranslations(
    from: TranslationOptions::PHP,
    path: null,
    locale: 'en',   // null = all locales
    group: 'auth',  // null = all groups
): TranslationCollection;
```

### listGroups — unique key group names

```php
$groups = TranslationHandler::listGroups(
    from: TranslationOptions::PHP,
    path: null,
    level: 0,       // 0 = top-level (e.g. "auth"), 1 = second-level, …
    search: null,   // case-insensitive filter
): Collection;
```

### deleteKey — delete a single key (optionally one locale)

```php
$count = TranslationHandler::deleteKey(
    from: TranslationOptions::PHP,
    key: 'auth.welcome',
    locale: 'en',   // null = delete all locales for the key
    path: null,
): int;
```

### deleteGroup — delete all keys under a group prefix

```php
$count = TranslationHandler::deleteGroup(
    from: TranslationOptions::PHP,
    group: 'auth',  // removes all "auth.*" keys
    path: null,
): int;
```

### sortKeys — sort keys alphabetically (PHP, JSON, CSV only)

```php
$count = TranslationHandler::sortKeys(
    from: TranslationOptions::PHP,
    locales: ['en'],   // [] = all locales
    groups: ['auth'],  // [] = all groups
    path: null,
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

### Sync translations between formats

```bash
php artisan translation-handler:sync {from?} {to?} [options]
php artisan translation-handler:sync php_file db --force
```

### Import / Export

```bash
php artisan translation-handler:import [options]   # uses config defaults
php artisan translation-handler:export [options]   # uses config defaults
```

### List translations

```bash
php artisan translation-handler:list {from?} {--from-path=} {--locale=} {--group=}
php artisan translation-handler:list php_file --locale=en --group=auth
```

### List key groups

```bash
php artisan translation-handler:list-groups {from?} {--from-path=} {--level=0} {--search=}
php artisan translation-handler:list-groups php_file --level=1 --search=messages
```

### Find a specific translation

```bash
php artisan translation-handler:find {from?} {key?} {locale?} {--from-path=}
php artisan translation-handler:find php_file auth.welcome en
```

### Get a single translation value

```bash
php artisan translation-handler:get {from?} {key?} {locale?} {--from-path=}
```

### Set a single translation

```bash
php artisan translation-handler:set {to?} {key?} {locale?} {value?} {--to-path=} {--force}
php artisan translation-handler:set json_file auth.welcome en "Welcome!" --force
```

### Delete a translation key

```bash
php artisan translation-handler:delete {from?} {key?} {--locale=} {--from-path=}
php artisan translation-handler:delete php_file auth.welcome          # all locales
php artisan translation-handler:delete php_file auth.welcome --locale=en
```

### Delete a translation group

```bash
php artisan translation-handler:delete-group {from?} {group?} {--from-path=}
php artisan translation-handler:delete-group php_file auth
```

### Sort translation keys alphabetically (PHP, JSON, CSV only)

```bash
php artisan translation-handler:sort {from?} {--from-path=} {--locale=*} {--group=*}
php artisan translation-handler:sort php_file
php artisan translation-handler:sort php_file --locale=en --group=auth
```

### Shared options (sync, import, export)

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

### Delete a key from all formats

```php
TranslationHandler::deleteKey(TranslationOptions::PHP, 'auth.old_key');
TranslationHandler::deleteKey(TranslationOptions::DB, 'auth.old_key');
```

### Delete a key for one locale only

```php
TranslationHandler::deleteKey(TranslationOptions::PHP, 'auth.welcome', locale: 'fr');
```

### Remove a whole group

```php
TranslationHandler::deleteGroup(TranslationOptions::PHP, 'legacy');
```

### Sort keys after a bulk import

```php
TranslationHandler::sortKeys(TranslationOptions::PHP);
TranslationHandler::sortKeys(TranslationOptions::JSON, locales: ['en', 'it']);
```

## MCP Tools (available when laravel/boost is installed)

The following tools are injected automatically into Boost's MCP server. No configuration required — they register on detection of `laravel/boost`.

Format values for `format` / `from` / `to` parameters: `php_file`, `json_file`, `csv_file`, `db`.

### Recommended workflow for editing translations

**Always use `db` as the working format for individual reads and writes, then sync to files at the end.**

File-based formats (PHP, JSON, CSV) rewrite the entire file on every `set` call. When adding or updating multiple keys, this means repeated full-file I/O. The database format writes only the affected rows, making each operation significantly faster.

Preferred pattern:

```
1. read/browse   → use db (or any format for read-only queries)
2. write keys    → always use db
3. finalise      → call sync-translations-tool from: db, to: <target file format>
```

**When to deviate:**
- You are making a single change and no DB is configured → write directly to the file format.
- The project does not use DB translations at all → write to the file format directly, but batch as many keys as possible in a single `set-all-locales-translation-tool` call rather than one call per locale.

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

### `list-translation-groups-tool` (read-only, idempotent)

Lists unique translation key groups at a given depth level. A group is a key prefix — `auth` is a level-0 group, `auth.messages` is a level-1 group. Level equals the number of delimiters in the group name.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string (enum) | yes | One of the format values |
| `level` | integer | no | Number of delimiters in the group name. `0` = top-level (e.g. `auth`), `1` = second-level (e.g. `auth.messages`). Defaults to `0`. |
| `search` | string | no | Case-insensitive filter — only groups whose name contains this string are returned. |

### `set-all-locales-translation-tool` (write)

Sets or updates a translation key for all locales at once.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string (enum) | yes | One of the format values |
| `key` | string | yes | Dot-delimited translation key (e.g. `auth.welcome`) |
| `values` | object | yes | Map of locale → value (e.g. `{"en": "Hello", "it": "Ciao"}`) |
| `force` | boolean | no | Overwrite existing values (default `false`) |

### `set-translation-group-tool` (write)

Sets or updates every translation under a group prefix in a single call. Use this when localising an entire group at once — the tool joins each subkey to the group with the configured delimiter and writes all locale values for every subkey.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string (enum) | yes | One of the format values |
| `group` | string | yes | Group prefix (e.g. `auth`). A trailing delimiter is tolerated. |
| `translations` | object | yes | Map of subkey → locale=>value object, e.g. `{"welcome": {"en": "Welcome", "it": "Benvenuto"}, "logout": {"en": "Logout", "it": "Esci"}}`. Subkeys may contain the delimiter (e.g. `nested.deep`). |
| `force` | boolean | no | Overwrite existing values (default `false`) |

### `sync-translations-tool` (write)

Copies translations from one storage format to another.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `from` | string (enum) | yes | Source format |
| `to` | string (enum) | yes | Destination format (must differ from `from`) |
| `force` | boolean | no | Overwrite existing translations in destination (default `false`) |

### `delete-translation-tool` (write)

Deletes a single translation key. Omit `locale` to delete all locales for the key.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string (enum) | yes | Storage format to delete from |
| `key` | string | yes | Dot-delimited key (e.g. `auth.welcome`) |
| `locale` | string | no | Delete only this locale. Omit to delete all locales. |

### `delete-translation-group-tool` (write)

Deletes all keys under a group prefix.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string (enum) | yes | Storage format to delete from |
| `group` | string | yes | Group prefix — all keys starting with `{group}.` are removed (e.g. `auth` removes all `auth.*`) |

### `sort-translations-tool` (write)

Sorts translation keys alphabetically within a format. Supports `php_file`, `json_file`, and `csv_file` only — not `db`.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `format` | string (enum) | yes | `php_file`, `json_file`, or `csv_file` |
| `locales` | array | no | Restrict sorting to these locales. Omit to sort all locales. |
| `groups` | array | no | Restrict sorting to these group prefixes. Omit to sort all groups. |

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
