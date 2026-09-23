<?php

namespace BrunosCode\TranslationHandler\Collections;

use BrunosCode\TranslationHandler\Data\Translation;
use Illuminate\Support\Collection;

class TranslationCollection extends Collection
{
    public function clone(): self
    {
        return clone $this;
    }

    public function addTranslation(Translation $newTranslation): self
    {
        $oldTranslationKey = $this->searchTranslation($newTranslation);

        if ($oldTranslationKey !== false) {
            return $this;
        }

        return $this->push($newTranslation);
    }

    public function replaceTranslation(Translation $newTranslation): self
    {
        $oldTranslationKey = $this->searchTranslation($newTranslation);

        if ($oldTranslationKey === false) {
            return $this->push($newTranslation);
        }

        return $this->put($oldTranslationKey, $newTranslation);
    }

    public function addTranslations(TranslationCollection $newTranslations): self
    {
        $index = $this->indexByKeyAndLocale();

        foreach ($newTranslations as $newTranslation) {
            $indexKey = $newTranslation->key.'|'.$newTranslation->locale;

            if (isset($index[$indexKey])) {
                continue;
            }

            $this->push($newTranslation);
            $index[$indexKey] = true;
        }

        return $this;
    }

    public function replaceTranslations(TranslationCollection $newTranslations): self
    {
        $index = $this->indexByKeyAndLocale();

        foreach ($newTranslations as $newTranslation) {
            $indexKey = $newTranslation->key.'|'.$newTranslation->locale;

            if (isset($index[$indexKey])) {
                $this->put($index[$indexKey], $newTranslation);

                continue;
            }

            $this->push($newTranslation);
            $index[$indexKey] = array_key_last($this->items);
        }

        return $this;
    }

    /**
     * Offsets of the items keyed by "key|locale", so bulk merges run in
     * linear time instead of one search() per incoming translation.
     *
     * @return array<string, int|string>
     */
    private function indexByKeyAndLocale(): array
    {
        $index = [];

        foreach ($this->items as $offset => $translation) {
            $index[$translation->key.'|'.$translation->locale] = $offset;
        }

        return $index;
    }

    public function searchTranslation(Translation $translation): int|bool
    {
        return $this->search(fn (Translation $item) => $item->key === $translation->key && $item->locale === $translation->locale);
    }

    public function sortTranslations(): self
    {
        return $this
            ->sortBy(fn (Translation $translation) => $translation->locale)
            ->sortBy(fn (Translation $translation) => $translation->key);
    }

    public function whereLocale(string $locale): self
    {
        return $this->filter(fn (Translation $translation) => $translation->locale === $locale);
    }

    public function whereLocaleIn(array $locales): self
    {
        return $this->filter(fn (Translation $translation) => in_array($translation->locale, $locales));
    }

    public function whereKey(string $key): self
    {
        return $this->filter(fn (Translation $translation) => $translation->key === $key);
    }

    public function whereKeyIn(array $keys): self
    {
        return $this->filter(fn (Translation $translation) => in_array($translation->key, $keys));
    }

    public function whereValue(string $value): self
    {
        return $this->filter(function (Translation $translation) use ($value) {
            return $translation->value === $value;
        });
    }

    public function whereValueContains(string $value): self
    {
        return $this->filter(fn (Translation $translation) => str_contains($translation->value ?? '', $value));
    }

    public function whereValueIn(array $values): self
    {
        return $this->filter(fn (Translation $translation) => in_array($translation->value, $values));
    }

    public function whereGroup(string $group, string $separator = '.'): self
    {
        return $this->filter(function (Translation $translation) use ($group, $separator) {
            $startsWith = str_ends_with($group, $separator)
              ? $group
              : $group.$separator;

            return str_starts_with($translation->key, $startsWith);
        });
    }

    public function whereGroupIn(array $groups, string $separator = '.'): self
    {
        return $this->filter(function (Translation $translation) use ($groups, $separator) {
            foreach ($groups as $group) {
                $startsWith = str_ends_with($group, $separator)
                  ? $group
                  : $group.$separator;

                if (str_starts_with($translation->key, $startsWith)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Find a key that is used both as a value and as a parent of another key
     * within the same locale (e.g. `auth.a` and `auth.a.b`). Such a pair cannot
     * be represented in a nested lang file, and Laravel's translator would
     * return an array for the parent key anyway.
     *
     * @return array{leaf: string, child: string, locale: string}|null
     */
    public function findParentLeafConflict(string $delimiter = '.'): ?array
    {
        /** @var array<string, array<string, true>> $keysByLocale */
        $keysByLocale = [];

        foreach ($this as $translation) {
            $keysByLocale[$translation->locale][$translation->key] = true;
        }

        foreach ($keysByLocale as $locale => $keys) {
            foreach (array_keys($keys) as $key) {
                $segments = explode($delimiter, (string) $key);

                for ($i = count($segments) - 1; $i > 0; $i--) {
                    $prefix = implode($delimiter, array_slice($segments, 0, $i));

                    if (isset($keys[$prefix])) {
                        return ['leaf' => $prefix, 'child' => (string) $key, 'locale' => (string) $locale];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @throws \InvalidArgumentException when a key is both a value and a parent of another key
     */
    public function assertNoParentLeafConflicts(string $delimiter = '.'): self
    {
        $conflict = $this->findParentLeafConflict($delimiter);

        if ($conflict !== null) {
            throw new \InvalidArgumentException(sprintf(
                'Translation key "%s" (%s) cannot hold a value because "%s" is nested under it. Rename one of the two keys.',
                $conflict['leaf'],
                $conflict['locale'],
                $conflict['child'],
            ));
        }

        return $this;
    }

    public static function fake(int $count = 10): self
    {
        $collection = new self;

        foreach (range(1, $count) as $index) {
            $collection->addTranslation(Translation::fake());
        }

        return $collection;
    }
}
