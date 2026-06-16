---
name: translation-handler-development
description: Develop with brunoscode/laravel-translation-handler in PHP — write custom translation flows on the TranslationHandler facade and TranslationCollection, or extend the package itself (custom file/database handlers, a TranslationChecker subclass, the data objects). Use for code; for agent/MCP-driven translation editing see translation-handler-mcp.
---

# Translation Handler — Development

For driving translations from PHP: the facade, collections, data objects, and extension points.
For agent-driven editing via Boost MCP tools, use the `translation-handler-mcp` skill instead.

## Formats

Four interchangeable storage formats, referenced by constants on `TranslationOptions`:

| Constant | String value | Notes |
|----------|-------------|-------|
| `TranslationOptions::PHP`  | `php_file`  | Standard Laravel PHP arrays |
| `TranslationOptions::JSON` | `json_file` | JSON files |
| `TranslationOptions::CSV`  | `csv_file`  | CSV files |
| `TranslationOptions::DB`   | `db`        | Database rows (run migrations first) |

File formats rewrite the **entire file** on every write; `db` writes only affected rows. For bulk programmatic edits, work in `db` then `sync` to the file format once at the end.

## Data objects

```php
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Collections\TranslationCollection;

$t = new Translation(key: 'auth.welcome', locale: 'en', value: 'Welcome!'); // value nullable
$collection = new TranslationCollection([$t]);
```

`TranslationOptions` is the config snapshot — read from config by default; construct manually only to override.

## Facade

```php
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
```

| Method | Signature (key args) | Returns |
|--------|----------------------|---------|
| `import` / `export` | `(from, to, force=false, fromPath=null, toPath=null)` | `bool` |
| `sync`        | `(from, to, force=false, fromPath=null, toPath=null)` | `bool` |
| `get`         | `(from, path=null)` | `TranslationCollection` |
| `set`         | `(translations, to, path=null, force=false)` | `int` (count) |
| `find`        | `(from, key, locale, path=null)` | `?Translation` |
| `listTranslations` | `(from, path=null, locale=null, group=null)` | `TranslationCollection` |
| `listGroups`  | `(from, path=null, level=0, search=null)` | `Collection` |
| `deleteKey`   | `(from, key, locale=null, path=null)` | `int` |
| `deleteGroup` | `(from, group, path=null)` | `int` |
| `sortKeys`    | `(from, locales=[], groups=[], path=null)` | `int` — PHP/JSON/CSV only |
| `delete`      | `(from, path=null)` | `int` — wipes the whole format |

`null` path means the config default. `locale=null` / empty arrays mean "all".

Options at runtime: `setOption($k, $v)`, `getOption($k)`, `setOptions(new TranslationOptions([...]))`, `resetOptions()`.

## TranslationCollection

```php
// Filter (each returns a new filtered collection)
$c->whereLocale('en');   $c->whereLocaleIn(['en','it']);
$c->whereKey('auth.welcome'); $c->whereKeyIn([...]);
$c->whereGroup('auth');  $c->whereGroupIn(['auth','validation']); // 'auth' matches auth.*
$c->whereValue('Welcome!'); $c->whereValueContains('Welc'); $c->whereValueIn([...]);

// Add / replace
$c->addTranslation($t);          // skip if key+locale exists
$c->replaceTranslation($t);      // overwrite if key+locale exists
$c->addTranslations($other);     $c->replaceTranslations($other);

// Utilities
$c->searchTranslation($t);  $c->sortTranslations();  $c->clone();
```

## Common patterns

```php
// Migrate PHP → JSON
TranslationHandler::import(TranslationOptions::PHP, TranslationOptions::JSON, force: true);

// Read, modify, write back
$c = TranslationHandler::get(TranslationOptions::JSON);
$c->addTranslation(new Translation('auth.welcome', 'it', 'Benvenuto!'));
TranslationHandler::set($c, TranslationOptions::JSON, force: true);

// Merge two sources (DB keys not already present are added)
$base = TranslationHandler::get(TranslationOptions::PHP);
$base->addTranslations(TranslationHandler::get(TranslationOptions::DB));
TranslationHandler::set($base, TranslationOptions::JSON, force: true);

// Export one locale to CSV
$it = TranslationHandler::get(TranslationOptions::PHP)->whereLocale('it');
TranslationHandler::set($it, TranslationOptions::CSV, force: true);

// Delete a key everywhere / for one locale / a whole group
TranslationHandler::deleteKey(TranslationOptions::PHP, 'auth.old_key');
TranslationHandler::deleteKey(TranslationOptions::PHP, 'auth.welcome', locale: 'fr');
TranslationHandler::deleteGroup(TranslationOptions::PHP, 'legacy');
```

## Extending the package

All handler classes and the checker are swappable via config (`config/translation-handler.php`) or `setOption()`:

```php
'phpHandlerClass'  => \App\Translations\MyPhpHandler::class,   // implements FileHandlerInterface
'jsonHandlerClass' => ...,
'csvHandlerClass'  => ...,
'dbHandlerClass'   => \App\Translations\MyDbHandler::class,    // implements DatabaseHandlerInterface
'checkerClass'     => \App\Translations\MyChecker::class,      // extends TranslationChecker
```

**Custom file handler** — implement `FileHandlerInterface`:

```php
public function __construct(TranslationOptions $options);
public function get(?string $path = null): TranslationCollection;
public function put(TranslationCollection $translations, ?string $path = null): int;
public function delete(?string $path = null): int;
```

**Custom database handler** — implement `DatabaseHandlerInterface` (same shape, `?string $connection` instead of `$path`, and `delete(?string $connection = null, bool $hardDelete = false)`).

**Custom key extraction for `check`** — extend `TranslationChecker` and override `patternsFor(string $side): array`. It returns `['static' => [...regex], 'dynamic' => [...regex]]`; group 1 of each regex must capture the key (static) or prefix (dynamic). The scanned paths/extensions per side come from the `check` config entry.

## Configuration

Publish: `php artisan vendor:publish --provider="BrunosCode\TranslationHandler\TranslationHandlerServiceProvider"`.

```php
'keyDelimiter' => '.',          'fileNames' => ['translation-handler'], 'locales' => ['en'],
'defaultImportFrom' => 'php_file', 'defaultImportTo' => 'json_file',
'defaultExportFrom' => 'json_file','defaultExportTo' => 'php_file',
'phpPath' => lang_path(),  'phpFormat' => false,
'jsonPath' => lang_path(), 'jsonFileName' => '', 'jsonNested' => false, 'jsonFormat' => true,
'csvPath' => storage_path('lang'), 'csvFileName' => 'translations', 'csvDelimiter' => ';', // ≠ keyDelimiter
'check' => [
    'backend'  => ['paths' => ['app','resources/views','routes','database'], 'extensions' => ['php']],
    'frontend' => ['paths' => ['resources/js'], 'extensions' => ['ts','tsx','js','jsx']],
    // optional per-side 'patterns' => ['static' => [...], 'dynamic' => [...]] overrides the regexes
],
```
