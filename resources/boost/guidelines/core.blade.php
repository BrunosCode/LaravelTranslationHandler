## Laravel Translation Handler (brunoscode/laravel-translation-handler)

Keeps an app's translations in sync across four interchangeable formats — PHP files (`php_file`), JSON files (`json_file`), CSV (`csv_file`), and database (`db`) — so one format can be generated from another instead of being maintained by hand.

**What the package is for** (the two workflows it was built around):

1. **Client-managed translations on staging/production.** Translations live in the `db` format so they can be edited directly in the running environment — by the client, without touching files or redeploying.
2. **PHP files as the single source, frontend generated from them (or the inverse).** Keep `php_file` as the source of truth and generate the JSON counterpart the frontend consumes — or go the other way (`json_file` → `php_file`). The two formats are kept consistent: edit one side, regenerate the other.

### Working with translations (AI agents)

**Always read and write translation content through this package's Boost MCP tools — never hand-edit `lang/*.php` or `lang/*.json` files directly.** A manual edit touches only one format and lets PHP and JSON drift apart; the tools write through the package so the formats stay consistent and the change can be synced.

- **Browse / read:** `list-translation-groups-tool`, `list-translations-tool`, `find-translation-tool`.
- **Write:** `set-translation-tool` (one key+locale), `set-all-locales-translation-tool` (one key, every locale), `set-translation-group-tool` (a whole group in one call).
- **Move content between formats:** `sync-translations-tool` (e.g. `db` → `php_file`, or `php_file` → `json_file` for the frontend).
- **At the end of the work — before committing — run `check-translations-tool`** to confirm no key referenced in the source code is missing for a locale. A non-zero `totalMissing` means there is still work to do — fill the gaps and re-check until it passes.

No MCP in this session? The same operations exist as `php artisan translation-handler:*` commands — see the `translation-handler-mcp` skill.

### Edit in `db`, sync to files

File formats (`php_file`, `json_file`, `csv_file`) rewrite the whole file on every write; `db` writes only the touched rows. For any multi-key edit, write to `db` first, then `sync` to the target file format once at the end. Deviate only for a single change, or when the project uses no DB translations — then batch keys into one `set-all-locales` / `set-group` call rather than one write per key.

### Custom PHP

For writing code against the package (custom flows on the facade, or extending it), use the `translation-handler-development` skill. Quick reference:

@verbatim
<code-snippet name="Generate the frontend JSON from PHP source files" lang="php">
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

TranslationHandler::sync(TranslationOptions::PHP, TranslationOptions::JSON, force: true);
</code-snippet>

<code-snippet name="Read, add a translation, write back" lang="php">
use BrunosCode\TranslationHandler\Data\Translation;

$collection = TranslationHandler::get(TranslationOptions::JSON);
$collection->addTranslation(new Translation('auth.welcome', 'en', 'Welcome!'));
TranslationHandler::set($collection, TranslationOptions::JSON, force: true);
</code-snippet>
@endverbatim

### Skills

- `translation-handler-mcp` — managing a project's translations via the Boost MCP tools or Artisan commands (the db-then-sync workflow, group/all-locale writes, missing-key checks).
- `translation-handler-development` — writing custom PHP: the facade, `TranslationCollection`, data objects, and extending the file/database handlers or `TranslationChecker`.
