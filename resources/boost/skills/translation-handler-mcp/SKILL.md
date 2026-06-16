---
name: translation-handler-mcp
description: Manage a Laravel project's translations through the brunoscode/laravel-translation-handler Boost MCP tools (or equivalent Artisan commands) — add, update, translate whole groups, sync between PHP/JSON/CSV/db formats, and check source code for missing or orphan keys. Use when editing a project's translation content; for writing custom PHP, see translation-handler-development.
---

# Translation Handler — MCP & CLI

When `laravel/boost` (v2) is installed, this package auto-registers translation MCP tools (no config). The tool schemas (parameters, enums) come from the MCP server itself — this skill is the **workflow**: which tool, in what order. Format values everywhere: `php_file`, `json_file`, `csv_file`, `db`.

## Golden rule: edit in `db`, sync to files at the end

File formats (PHP/JSON/CSV) rewrite the **whole file** on every write — N keys means N full-file rewrites. `db` writes only the touched rows. So for any multi-key edit:

```
1. read/browse  → any format (db preferred)
2. write keys   → always db
3. finalise     → sync-translations-tool  from: db  to: <target file format>
```

Deviate only when:
- **Single change, no DB configured** → write straight to the file format.
- **Project has no DB translations** → write to the file format, but batch with `set-all-locales-translation-tool` / `set-translation-group-tool` (one call, many keys) rather than one `set` per locale.

Call `get-translation-config-tool` first if unsure which formats/locales the project uses.

## Tool map

**Read (idempotent):**
- `get-translation-config-tool` — locales, fileNames, delimiter, default formats, paths. Good first call.
- `list-translation-groups-tool` — explore the key hierarchy by depth (`level` = delimiters in the group name) before reading/writing in a large project.
- `list-translations-tool` — list a format's keys, optionally filtered by `locale` / `group`.
- `find-translation-tool` — one key + locale.

**Write:**
- `set-translation-tool` — one key, one locale.
- `set-all-locales-translation-tool` — one key, all locales in a single call (`values: {en:…, it:…}`).
- `set-translation-group-tool` — a whole group at once (`translations: {subkey: {en:…, it:…}}`). Best for localising a feature in one shot.
- `sync-translations-tool` — copy `from` → `to` (different formats). This is step 3 of the golden rule.
- `delete-translation-tool` — one key (omit `locale` to drop all locales).
- `delete-translation-group-tool` — every key under a prefix.
- `sort-translations-tool` — alphabetical sort; `php_file`/`json_file`/`csv_file` only (not `db`).

**Audit:**
- `check-translations-tool` — scans source code for `__()/trans()`-style usages and reports keys referenced but undefined, per side/locale. Returns `passed`, `totalMissing`; set `orphans: true` to also list defined-but-unused keys.

## Typical flows

**Localise a feature (many keys):** `get-translation-config-tool` → `set-translation-group-tool` on `db` → `sync-translations-tool` db → `php_file`.

**Fix one string:** `set-translation-tool` directly on the file format (single write, no sync needed).

**Find gaps before release:** `check-translations-tool` (per `side`) → fill missing keys in `db` → `sync` to files → re-run `check` to confirm `passed: true`.

## Without MCP (Artisan)

Same operations from the CLI:

```bash
php artisan translation-handler:sync php_file db --force
php artisan translation-handler:import        # uses config defaults
php artisan translation-handler:export
php artisan translation-handler:list php_file --locale=en --group=auth
php artisan translation-handler:list-groups php_file --level=1 --search=messages
php artisan translation-handler:find php_file auth.welcome en
php artisan translation-handler:get  php_file auth.welcome en
php artisan translation-handler:set  json_file auth.welcome en "Welcome!" --force
php artisan translation-handler:delete php_file auth.welcome [--locale=en]
php artisan translation-handler:delete-group php_file auth
php artisan translation-handler:sort php_file [--locale=en --group=auth]
php artisan translation-handler:check php_file --show-keys [--side=backend --orphans]
```

Shared options on `sync`/`import`/`export`: `--force` (overwrite), `--fresh` (wipe before write), `--file-names=*`, `--locales=*`, `--from-path=`, `--to-path=`, `--guided` (interactive).
