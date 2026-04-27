## Laravel Translation Handler (brunoscode/laravel-translation-handler)

Manages translations across four interchangeable formats: PHP files (`php_file`), JSON files (`json_file`), CSV files (`csv_file`), and database (`db`). Use the `TranslationHandler` facade or Artisan commands to import, export, get, set, and delete translations across locales.

### Key classes

- `TranslationHandler` — facade for all operations
- `TranslationOptions::PHP / JSON / CSV / DB` — format constants
- `Translation` — single entry with `key`, `locale`, `value`
- `TranslationCollection` — filterable collection of `Translation` objects

### Quick reference

@verbatim
<code-snippet name="Import PHP translations to JSON" lang="php">
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

TranslationHandler::import(TranslationOptions::PHP, TranslationOptions::JSON, force: true);
</code-snippet>

<code-snippet name="Read, add a translation, write back" lang="php">
use BrunosCode\TranslationHandler\Data\Translation;

$collection = TranslationHandler::get(TranslationOptions::JSON);
$collection->addTranslation(new Translation('auth.welcome', 'en', 'Welcome!'));
TranslationHandler::set($collection, TranslationOptions::JSON, force: true);
</code-snippet>

<code-snippet name="Filter by locale and export to CSV" lang="php">
$all = TranslationHandler::get(TranslationOptions::PHP);
TranslationHandler::set($all->whereLocale('it'), TranslationOptions::CSV, force: true);
</code-snippet>
@endverbatim
