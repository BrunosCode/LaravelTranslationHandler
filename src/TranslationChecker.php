<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use Symfony\Component\Finder\Finder;

class TranslationChecker
{
    protected string $delimiter;

    public function __construct(
        protected TranslationOptions $options
    ) {
        $this->delimiter = $options->keyDelimiter;
    }

    /**
     * The configured sides to scan, derived from the keys of the `check` option.
     *
     * @return string[]
     */
    public function sides(): array
    {
        return array_keys($this->options->check);
    }

    /**
     * Scan the configured source and report keys referenced in code but not
     * defined per locale, plus (optionally) keys defined but never referenced.
     *
     * The defined translations to check against are passed in by the caller
     * (the service fetches and scopes them); this class only scans source and
     * compares.
     *
     * @param  string[]  $locales
     * @param  string[]|null  $sides  Defaults to all configured sides when null.
     * @return array{
     *     locales: string[],
     *     sides: array<string, array{
     *         staticKeys: int,
     *         prefixes: int,
     *         total: int,
     *         locales: array<string, array{keys: string[], prefixes: string[], total: int}>
     *     }>,
     *     orphans: array<string, string[]>|null,
     *     totalMissing: int
     * }
     */
    public function check(TranslationCollection $translations, array $locales, ?array $sides = null, bool $includeOrphans = false): array
    {
        $sides ??= $this->sides();

        $existing = $this->loadExistingKeys($translations, $locales);

        $allUsages = [];
        $sidesReport = [];
        $totalMissing = 0;

        foreach ($sides as $side) {
            $usages = $this->scanFiles($side);
            $allUsages[$side] = $usages;

            $localeReport = [];
            $sideTotal = 0;
            foreach ($locales as $locale) {
                $missing = $this->missingForLocale($usages, $existing[$locale] ?? []);
                $localeReport[$locale] = $missing;
                $sideTotal += $missing['total'];
            }

            $sidesReport[$side] = [
                'staticKeys' => count($usages['static']),
                'prefixes' => count($usages['prefixes']),
                'total' => $sideTotal,
                'locales' => $localeReport,
            ];

            $totalMissing += $sideTotal;
        }

        return [
            'locales' => array_values($locales),
            'sides' => $sidesReport,
            'orphans' => $includeOrphans ? $this->orphans($allUsages, $existing, $locales) : null,
            'totalMissing' => $totalMissing,
        ];
    }

    /**
     * Group the defined keys of the given collection per locale.
     *
     * When `checkIncludeFrameworkKeys` is enabled, Laravel's own bundled lang
     * keys are merged into every locale so references the framework resolves
     * via its built-in files are not reported as missing.
     *
     * @param  string[]  $locales
     * @return array<string, string[]>
     */
    public function loadExistingKeys(TranslationCollection $translations, array $locales): array
    {
        $result = [];
        $frameworkKeys = $this->options->checkIncludeFrameworkKeys ? $this->frameworkKeys() : [];

        foreach ($locales as $locale) {
            $result[$locale] = $translations->whereLocale($locale)
                ->pluck('key')
                ->merge($frameworkKeys)
                ->unique()
                ->values()
                ->all();
        }

        return $result;
    }

    /**
     * The translation keys shipped with Laravel's own lang files (auth,
     * pagination, passwords, validation). The framework's translator falls
     * back to these even when a project never publishes them, so they count as
     * defined when `checkIncludeFrameworkKeys` is enabled.
     *
     * Read straight from the framework's bundled `en` lang directory (the only
     * locale Laravel ships) and flattened to the configured key delimiter,
     * independent of the project's `fileNames`. Returns an empty list when the
     * directory is absent (e.g. a non-standard install layout).
     *
     * @return string[]
     */
    protected function frameworkKeys(): array
    {
        $dir = base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en');

        if (! is_dir($dir)) {
            return [];
        }

        $keys = [];
        foreach (glob("{$dir}/*.php") ?: [] as $file) {
            $group = pathinfo($file, PATHINFO_FILENAME);
            $contents = require $file;

            if (! is_array($contents)) {
                continue;
            }

            foreach ($this->flattenKeys($contents, $group) as $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Flatten a nested translation array into fully-qualified keys joined by
     * the configured delimiter, e.g. `['custom' => ['x' => '…']]` under prefix
     * `validation` becomes `validation{delimiter}custom{delimiter}x`.
     *
     * @param  array<int|string, mixed>  $items
     * @return string[]
     */
    protected function flattenKeys(array $items, string $prefix): array
    {
        $keys = [];
        foreach ($items as $key => $value) {
            $fullKey = $prefix.$this->delimiter.$key;

            if (is_array($value)) {
                foreach ($this->flattenKeys($value, $fullKey) as $nested) {
                    $keys[] = $nested;
                }
            } else {
                $keys[] = $fullKey;
            }
        }

        return $keys;
    }

    /**
     * @param  array{static: string[], prefixes: string[]}  $usages
     * @param  string[]  $existingKeys
     * @return array{keys: string[], prefixes: string[], total: int}
     */
    public function missingForLocale(array $usages, array $existingKeys): array
    {
        $existingFlip = array_flip($existingKeys);

        $missingKeys = [];
        foreach ($usages['static'] as $key) {
            if (! isset($existingFlip[$key])) {
                $missingKeys[$key] = true;
            }
        }

        $missingPrefixes = [];
        foreach ($usages['prefixes'] as $prefix) {
            $hasMatch = false;
            foreach ($existingKeys as $k) {
                if (str_starts_with($k, $prefix)) {
                    $hasMatch = true;
                    break;
                }
            }
            if (! $hasMatch) {
                $missingPrefixes[$prefix] = true;
            }
        }

        $keys = array_keys($missingKeys);
        $prefixes = array_keys($missingPrefixes);
        sort($keys);
        sort($prefixes);

        return [
            'keys' => $keys,
            'prefixes' => $prefixes,
            'total' => count($keys) + count($prefixes),
        ];
    }

    /**
     * @param  array<string, array{static: string[], prefixes: string[]}>  $allUsages
     * @param  array<string, string[]>  $existing
     * @param  string[]  $locales
     * @return array<string, string[]>
     */
    public function orphans(array $allUsages, array $existing, array $locales): array
    {
        $staticUsed = [];
        $prefixesUsed = [];
        foreach ($allUsages as $usages) {
            foreach ($usages['static'] as $k) {
                $staticUsed[$k] = true;
            }
            foreach ($usages['prefixes'] as $p) {
                $prefixesUsed[$p] = true;
            }
        }
        $prefixesUsedList = array_keys($prefixesUsed);

        $result = [];
        foreach ($locales as $locale) {
            $orphans = [];
            foreach ($existing[$locale] ?? [] as $key) {
                if (isset($staticUsed[$key])) {
                    continue;
                }
                $matched = false;
                foreach ($prefixesUsedList as $prefix) {
                    if (str_starts_with($key, $prefix)) {
                        $matched = true;
                        break;
                    }
                }
                if (! $matched) {
                    $orphans[] = $key;
                }
            }
            sort($orphans);
            $result[$locale] = $orphans;
        }

        return $result;
    }

    /**
     * @return array{static: string[], prefixes: string[]}
     */
    public function scanFiles(string $side): array
    {
        /** @var string[] $paths */
        $paths = $this->options->check[$side]['paths'] ?? [];
        /** @var string[] $extensions */
        $extensions = $this->options->check[$side]['extensions'] ?? [];

        $dirs = array_values(array_filter(
            array_map(fn (string $d): string => $this->resolvePath($d), $paths),
            fn (string $d): bool => is_dir($d),
        ));

        if (empty($dirs) || empty($extensions)) {
            return ['static' => [], 'prefixes' => []];
        }

        $finder = (new Finder)
            ->files()
            ->in($dirs)
            ->name(array_map(fn (string $e): string => "*.{$e}", $extensions));

        $patterns = $this->patternsFor($side);

        $staticKeys = [];
        $prefixes = [];

        foreach ($finder as $file) {
            $code = $file->getContents();

            foreach ($patterns['static'] as $regex) {
                if (preg_match_all($regex, $code, $matches) > 0) {
                    foreach ($matches[1] as $k) {
                        if ($k !== '' && str_contains($k, '.') && ! str_ends_with($k, '.')) {
                            $staticKeys[$this->normalizeKey($k)] = true;
                        }
                    }
                }
            }

            foreach ($patterns['dynamic'] as $regex) {
                if (preg_match_all($regex, $code, $matches) > 0) {
                    foreach ($matches[1] as $p) {
                        if ($p !== '' && str_ends_with($p, '.')) {
                            $prefixes[$this->normalizeKey($p)] = true;
                        }
                    }
                }
            }
        }

        return [
            'static' => array_keys($staticKeys),
            'prefixes' => array_keys($prefixes),
        ];
    }

    /**
     * Translation keys in source code follow Laravel's dot convention. Convert
     * them to the configured key delimiter so they line up with stored keys.
     */
    protected function normalizeKey(string $key): string
    {
        return $this->delimiter === '.' ? $key : str_replace('.', $this->delimiter, $key);
    }

    protected function resolvePath(string $path): string
    {
        return $this->isAbsolutePath($path) ? $path : base_path($path);
    }

    /**
     * Cross-platform absolute-path check: Unix `/…`, Windows `C:\…` / `C:/…`,
     * and UNC `\\…`.
     */
    protected function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || (strlen($path) > 1 && ctype_alpha($path[0]) && $path[1] === ':');
    }

    /**
     * Regular expressions used to extract translation usages from source code,
     * keyed by `static` (full keys) and `dynamic` (key prefixes). Each pattern
     * must capture the key (or key prefix) in group 1.
     *
     * Patterns declared under `check.{side}.patterns` in the config take
     * precedence; otherwise the bundled defaults for that side are used.
     * Override this method (via the `checkerClass` config option) when you need
     * to build patterns programmatically rather than from config.
     *
     * @return array{static: string[], dynamic: string[]}
     */
    protected function patternsFor(string $side): array
    {
        $configured = $this->options->check[$side]['patterns'] ?? null;

        if (is_array($configured)) {
            return [
                'static' => $configured['static'] ?? [],
                'dynamic' => $configured['dynamic'] ?? [],
            ];
        }

        return $this->defaultPatternsFor($side);
    }

    /**
     * The bundled extraction patterns, applied when a side declares no custom
     * `patterns` in the config. The side named `backend` gets PHP translation
     * patterns; every other side gets JS/TS patterns.
     *
     * @return array{static: string[], dynamic: string[]}
     */
    protected function defaultPatternsFor(string $side): array
    {
        if ($side === 'backend') {
            $func = '(?:__|trans_choice|trans|Lang::get|@lang)';

            return [
                'static' => [
                    "/{$func}\\s*\\(\\s*'([^'\\\\]+)'/",
                    "/{$func}\\s*\\(\\s*\"([^\"\\\\\\$]+)\"/",
                ],
                'dynamic' => [
                    "/{$func}\\s*\\(\\s*['\"]([^'\"]+\\.)['\"]\\s*\\.\\s*\\\$/",
                    "/{$func}\\s*\\(\\s*\"([^\"]*\\.)(?:\\\$\\w|\\{\\\$)/",
                ],
            ];
        }

        $call = '(?<![\\w\$])(?:i18next?\\.t|t)';

        return [
            'static' => [
                "/{$call}\\s*\\(\\s*'([^'\\\\]+)'/",
                "/{$call}\\s*\\(\\s*\"([^\"\\\\]+)\"/",
                "/{$call}\\s*\\(\\s*`([^`\\\$]+)`/",
            ],
            'dynamic' => [
                "/{$call}\\s*\\(\\s*['\"]([^'\"]+\\.)['\"]\\s*\\+/",
                "/{$call}\\s*\\(\\s*`([^`]*\\.)\\\${/",
            ],
        ];
    }
}
