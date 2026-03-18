# Changelog

All notable changes to `laravel-translation-handler` will be documented in this file.

## v1.0.0 - 2026-03-18

### First stable release

Laravel Translation Handler is a Laravel package to move, import, and export translations across PHP files, JSON files, CSV files, and database — all via artisan commands or a clean Facade API.

### Features

- **Four translation formats**: PHP files, JSON files, CSV files, and database
- **Artisan commands**: `translation-handler`, `translation-handler:import`, `translation-handler:export`, `translation-handler:get`, `translation-handler:set`
- **Guided mode**: interactive `--guided` flag on import/export/move commands
- **`--fresh` option**: delete existing translations before writing
- **`--force` option**: overwrite existing translations
- **Custom paths**: `--from-path` / `--to-path` per command
- **Facade API**: `TranslationHandler::import()`, `::export()`, `::get()`, `::set()`, `::delete()`
- **Configurable options**: key delimiter, locales, file names, per-format paths, and custom handler classes
- **Nested JSON support**: `jsonNested` option to mirror PHP file structure in JSON output

### Improvements

- Fixed `--from` and `--to` option handling in `import` and `export` commands
- Improved error messages across all handlers for clearer diagnostics
- Added `--fresh` option to commands
- Nested translation tests

### Requirements

| Laravel | PHP        |
|---------|------------|
| 11.x    | 8.2, 8.3   |
| 10.x    | 8.2, 8.3   |

### CI

- Test matrix covers PHP 8.2, 8.3 × Laravel 10, 11
- Removed `prefer-lowest` stability from matrix (tests realistic dependency combinations only)

---

## v0.1.2 - 2025-02-14

Remove Laravel 10 required
