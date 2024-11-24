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
        $newTranslations->each(function (Translation $newTranslation) {
            $oldTranslationKey = $this->searchTranslation($newTranslation);

            if ($oldTranslationKey !== false) {
                return;
            }

            $this->push($newTranslation);
        });

        return $this;
    }

    public function replaceTranslations(TranslationCollection $newTranslations): self
    {
        $newTranslations->each(function (Translation $newTranslation) {
            $oldTranslationKey = $this->searchTranslation($newTranslation);

            if ($oldTranslationKey === false) {
                $this->push($newTranslation);

                return;
            }

            $this->put($oldTranslationKey, $newTranslation);
        });

        return $this;
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
        return $this->filter(fn (Translation $translation) => str_contains($translation->value, $value));
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

    public static function fake(int $count = 10): self
    {
        $collection = new self;

        foreach (range(1, $count) as $index) {
            $collection->addTranslation(Translation::fake());
        }

        return $collection;
    }
}
