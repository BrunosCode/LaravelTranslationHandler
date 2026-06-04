<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Interfaces\DatabaseHandlerInterface;
use BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface;
use Illuminate\Support\Collection;

class TranslationHandlerService
{
    protected TranslationOptions $defaultOptions;

    protected ?TranslationOptions $options;

    public function __construct()
    {
        $this->defaultOptions = new TranslationOptions;
        $this->options = null;
    }

    public function find(string $from, string $key, string $locale, ?string $path = null): ?Translation
    {
        return $this->get($from, $path)->whereKey($key)->whereLocale($locale)->first();
    }

    public function listTranslations(string $from, ?string $path = null, ?string $locale = null, ?string $group = null): TranslationCollection
    {
        $collection = $this->get($from, $path);

        if ($locale) {
            $collection = $collection->whereLocale($locale);
        }

        if ($group) {
            $collection = $collection->whereGroup($group);
        }

        return $collection;
    }

    public function listGroups(string $from, ?string $path = null, int $level = 0, ?string $search = null): Collection
    {
        $delimiter = $this->getOption('keyDelimiter') ?? '.';
        $depth = $level + 1;

        return $this->get($from, $path)
            ->map(fn ($t) => $t->key)
            ->unique()
            ->map(function ($key) use ($delimiter, $depth) {
                $segments = explode($delimiter, $key);

                if (count($segments) <= $depth) {
                    return null;
                }

                return implode($delimiter, array_slice($segments, 0, $depth));
            })
            ->filter()
            ->unique()
            ->when($search, fn ($items) => $items->filter(
                fn ($group) => str_contains(strtolower($group), strtolower($search))
            ))
            ->sort()
            ->values();
    }

    public function sync(string $from, string $to, bool $force = false, ?string $fromPath = null, ?string $toPath = null): false|int
    {
        $translations = $this->get($from, $fromPath);

        if ($translations->isEmpty()) {
            return false;
        }

        return $this->set($translations, $to, $toPath, $force);
    }

    public function import(?string $from = null, ?string $to = null, bool $force = false, ?string $fromPath = null, ?string $toPath = null): false|int
    {
        $options = $this->getOptions();

        $from = $from ?? $options->defaultImportFrom;

        $to = $to ?? $options->defaultImportTo;

        $translations = $this->get($from, $fromPath);

        if ($translations->isEmpty()) {
            return false;
        }

        return $this->set($translations, $to, $toPath, $force);
    }

    public function export(?string $from = null, ?string $to = null, bool $force = false, ?string $fromPath = null, ?string $toPath = null): false|int
    {
        $options = $this->getOptions();

        $from = $from ?? $options->defaultExportFrom;

        $to = $to ?? $options->defaultExportTo;

        $translations = $this->get($from, $fromPath);

        if ($translations->isEmpty()) {
            return false;
        }

        return $this->set($translations, $to, $toPath, $force);
    }

    public function get(string $from, ?string $path = null): TranslationCollection
    {
        $options = $this->getOptions();

        $translations = match ($from) {
            TranslationOptions::PHP => $this->getPhpHandler()->get(
                path: $path
            ),
            TranslationOptions::CSV => $this->getCsvHandler()->get(
                path: $path
            ),
            TranslationOptions::JSON => $this->getJsonHandler()->get(
                path: $path
            ),
            TranslationOptions::DB => $this->getDbHandler()->get(
                connection: $path
            ),
            default => throw new \InvalidArgumentException("Invalid from type '{$from}'. Valid types: ".implode(', ', TranslationOptions::TYPES)),
        };

        return $translations
            ->whereGroupIn($options->fileNames)
            ->whereLocaleIn($options->locales);
    }

    public function set(TranslationCollection $translations, string $to, ?string $path = null, bool $force = false): int
    {
        $oldTranslations = $this->get($to, $path);

        $newTranslations = $force
            ? $oldTranslations->replaceTranslations($translations)
            : $oldTranslations->addTranslations($translations);

        return $this->putCollection($to, $newTranslations->sortTranslations(), $path);
    }

    public function sortKeys(string $from, array $locales = [], array $groups = [], ?string $path = null): int
    {
        $collection = $this->get($from, $path);

        $target = $collection;

        if (! empty($locales)) {
            $target = $target->whereLocaleIn($locales);
        }

        if (! empty($groups)) {
            $target = $target->whereGroupIn($groups);
        }

        $count = $target->count();

        if ($count === 0) {
            return 0;
        }

        $sorted = $target->sortTranslations();
        $rest = $collection->reject(fn ($t) => $target->contains($t));
        $merged = new TranslationCollection([...$sorted->values()->all(), ...$rest->values()->all()]);

        $this->putCollection($from, $merged, $path);

        return $count;
    }

    public function deleteKey(string $from, string $key, ?string $locale = null, ?string $path = null): int
    {
        $collection = $this->get($from, $path);

        $survivors = $locale
            ? $collection->reject(fn ($t) => $t->key === $key && $t->locale === $locale)
            : $collection->reject(fn ($t) => $t->key === $key);

        $deleted = $collection->count() - $survivors->count();

        if ($deleted === 0) {
            return 0;
        }

        $this->putCollection($from, new TranslationCollection($survivors->values()->all()), $path);

        return $deleted;
    }

    public function deleteGroup(string $from, string $group, ?string $path = null): int
    {
        $delimiter = $this->getOption('keyDelimiter') ?? '.';
        $prefix = str_ends_with($group, $delimiter) ? $group : $group.$delimiter;

        $collection = $this->get($from, $path);

        $survivors = $collection->reject(fn ($t) => str_starts_with($t->key, $prefix));

        $deleted = $collection->count() - $survivors->count();

        if ($deleted === 0) {
            return 0;
        }

        $this->putCollection($from, new TranslationCollection($survivors->values()->all()), $path);

        return $deleted;
    }

    private function putCollection(string $to, TranslationCollection $collection, ?string $path): int
    {
        return match ($to) {
            TranslationOptions::PHP => $this->getPhpHandler()->put(translations: $collection, path: $path),
            TranslationOptions::CSV => $this->getCsvHandler()->put(translations: $collection, path: $path),
            TranslationOptions::JSON => $this->getJsonHandler()->put(translations: $collection, path: $path),
            TranslationOptions::DB => $this->getDbHandler()->put(translations: $collection, connection: $path),
            default => throw new \InvalidArgumentException("Invalid to type '{$to}'. Valid types: ".implode(', ', TranslationOptions::TYPES)),
        };
    }

    public function delete(string $from, ?string $path = null): int
    {
        return match ($from) {
            TranslationOptions::PHP => $this->getPhpHandler()->delete(
                path: $path
            ),
            TranslationOptions::CSV => $this->getCsvHandler()->delete(
                path: $path
            ),
            TranslationOptions::JSON => $this->getJsonHandler()->delete(
                path: $path
            ),
            TranslationOptions::DB => $this->getDbHandler()->delete(
                connection: $path
            ),
            default => throw new \InvalidArgumentException("Invalid from type '{$from}'. Valid types: ".implode(', ', TranslationOptions::TYPES)),
        };
    }

    /**
     * Scan the application source for translation usages and report keys
     * referenced in code but not defined per locale (and optionally orphans).
     *
     * @param  string[]  $locales
     * @param  string[]|null  $sides  Defaults to all configured sides when null.
     * @return array<string, mixed>
     */
    public function check(string $from, array $locales, ?array $sides = null, ?string $fromPath = null, bool $includeOrphans = false): array
    {
        $translations = $this->get($from, $fromPath);

        return array_merge(
            ['from' => $from],
            $this->getChecker()->check($translations, $locales, $sides, $includeOrphans),
        );
    }

    public function getChecker(): TranslationChecker
    {
        return app($this->getOptions()->checkerClass, ['options' => $this->getOptions()]);
    }

    public function getTypes(): array
    {
        return TranslationOptions::TYPES;
    }

    public function getPhpHandler(): FileHandlerInterface
    {
        return app($this->getOptions()->phpHandlerClass, ['options' => $this->getOptions()]);
    }

    public function getCsvHandler(): FileHandlerInterface
    {
        return app($this->getOptions()->csvHandlerClass, ['options' => $this->getOptions()]);
    }

    public function getJsonHandler(): FileHandlerInterface
    {
        return app($this->getOptions()->jsonHandlerClass, ['options' => $this->getOptions()]);
    }

    public function getDbHandler(): DatabaseHandlerInterface
    {
        return app($this->getOptions()->dbHandlerClass, ['options' => $this->getOptions()]);
    }

    public function getOptions(): TranslationOptions
    {
        return $this->options ?? $this->defaultOptions;
    }

    public function setOptions(TranslationOptions $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function resetOptions(): self
    {
        $this->options = null;

        return $this;
    }

    public function setOption(string $name, mixed $value): self
    {
        if ($this->options === null) {
            $this->options = clone $this->defaultOptions;
        }

        $this->options->$name = $value;

        return $this;
    }

    public function getOption(string $name): mixed
    {
        return $this->getOptions()->$name;
    }

    public function getDefaultOptions(): TranslationOptions
    {
        return $this->defaultOptions;
    }

    public function setDefaultOptions(TranslationOptions $options): self
    {
        $this->defaultOptions = $options;

        return $this;
    }

    public function setDefaultOption(string $name, mixed $value): self
    {
        $this->defaultOptions->$name = $value;

        return $this;
    }

    public function getDefaultOption(string $name): mixed
    {
        return $this->defaultOptions->$name;
    }
}
