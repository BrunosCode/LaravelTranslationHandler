<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface;
use Illuminate\Support\Facades\File;

class JsonFileHandler implements FileHandlerInterface
{
    public function __construct(
        protected TranslationOptions $options
    ) {}

    public function get(
        ?string $path = null,
        null|string|array $fileNames = null
    ): TranslationCollection {
        $translations = new TranslationCollection;

        foreach ($this->options->locales as $locale) {
            $path = $this->getFilePath($path, $fileNames, $locale);

            $rawTranslations = $this->read($path);

            $translations->addTranslations(
                $this->buildTranslations(
                    $rawTranslations,
                    $locale,
                    $this->options->jsonNested,
                    $this->options->keyDelimiter
                )
            );
        }

        return $translations;
    }

    public function put(
        TranslationCollection $translations,
        ?string $path = null,
        null|string|array $fileNames = null
    ): int {
        $counter = 0;

        foreach ($this->options->locales as $locale) {
            $filteredTranslations = $translations->whereLocale($locale);

            if ($filteredTranslations->isEmpty()) {
                continue;
            }

            $rawTranslations = $this->buildForFile(
                $filteredTranslations,
                $locale,
                $this->options->jsonNested,
                $this->options->keyDelimiter
            );

            $filePath = $this->getFilePath($path, $fileNames, $locale);

            $this->write($rawTranslations, $filePath, $this->options->jsonFormat);

            $counter += $filteredTranslations->count();
        }

        return $counter;
    }

    public function delete(
        ?string $path = null,
        null|string|array $fileNames = null
    ): int {
        $currentTranslations = $this->get($path);

        foreach ($this->options->locales as $locale) {
            if (! File::delete(
                $this->getFilePath($path, $fileNames, $locale)
            )) {
                return 0;
            }
        }

        return $currentTranslations->count();
    }

    protected function buildTranslations(
        array $rawTranslations,
        string $locale,
        bool $nested,
        string $keyDelimiter
    ): TranslationCollection {
        $translations = new TranslationCollection;

        if ($nested) {
            return $this->buildTranslationsFromNestedArray(
                $translations,
                $rawTranslations,
                null,
                $locale,
                $keyDelimiter
            );
        }

        return $this->buildTranslationsFromFlatArray(
            $translations,
            $locale,
            $rawTranslations
        );
    }

    protected function buildTranslationsFromFlatArray(
        TranslationCollection $translations,
        string $locale,
        array $rawTranslations
    ) {
        foreach ($rawTranslations as $key => $value) {
            $translations->addTranslation(new Translation($key, $locale, $value));
        }

        return $translations;
    }

    protected function buildTranslationsFromNestedArray(
        TranslationCollection $translations,
        string|array &$value,
        ?string $key,
        string $locale,
        string $keyDelimiter
    ): TranslationCollection {
        if (is_array($value)) {
            foreach ($value as $childKey => $childValue) {
                $currentKey = $key ? $key.$keyDelimiter.$childKey : $childKey;
                $translations = $this->buildTranslationsFromNestedArray(
                    $translations,
                    $childValue,
                    $currentKey,
                    $locale,
                    $keyDelimiter
                );
            }
        } else {
            $translations->addTranslation(new Translation($key, $locale, $value));
        }

        return $translations;
    }

    protected function read(
        string $filePath
    ): array {
        if (! File::exists($filePath)) {
            return [];
        }

        $fileContent = File::get($filePath);

        if (empty($fileContent)) {
            return [];
        }

        $rawTranslations = json_decode($fileContent, true);

        if (! is_array($rawTranslations)) {
            return [];
        }

        return $rawTranslations;
    }

    protected function buildForFile(
        TranslationCollection $translations,
        string $locale,
        bool $nested = false,
        ?string $keyDelimiter = null
    ): array {
        $fileTranslations = [];

        if ($nested) {
            return $this->buildForNestedFile(
                $fileTranslations,
                $translations,
                $locale,
                $keyDelimiter
            );
        }

        return $this->buildForFlatFile(
            $fileTranslations,
            $translations,
            $locale
        );
    }

    protected function buildForFlatFile(
        array $fileTranslations,
        TranslationCollection $translations,
        string $locale
    ): array {
        foreach ($translations as $translation) {
            if ($translation->locale != $locale) {
                continue;
            }

            $fileTranslations[$translation->key] = $translation->value;
        }

        return $fileTranslations;
    }

    protected function buildForNestedFile(
        array $fileTranslations,
        TranslationCollection $translations,
        string $locale,
        string $keyDelimiter
    ): array {
        foreach ($translations as $translation) {
            if ($translation->locale !== $locale) {
                continue;
            }

            $keys = explode($keyDelimiter, $translation->key);

            $current = &$fileTranslations;

            foreach ($keys as $key) {
                $current = &$current[$key];
            }

            $current = $translation->value;
        }

        return $fileTranslations;
    }

    protected function write(
        array $translations,
        string $filePath,
        bool $format = false
    ): bool {
        if (! File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0777, true);
        }

        return (bool) File::put(
            $filePath,
            json_encode($translations, $format ? JSON_PRETTY_PRINT : 0),
            false
        );
    }

    protected function getFilePath(
        ?string $path,
        null|string|array $fileNames,
        string $locale
    ): string {
        $path = $this->getPath($path);
        $fileName = $this->getFileName($fileNames);

        if (empty($fileName)) {
            return "{$path}/{$locale}.json";
        }

        return "{$path}/{$locale}/{$fileName}.json";
    }

    protected function getPath(
        ?string $path
    ): string {
        $path ??= $this->options->jsonPath;

        if (is_string($path) && empty($path)) {
            throw new \InvalidArgumentException('Path cannot be empty');
        }

        return $path;
    }

    protected function getFileName(
        null|string|array $fileNames
    ): ?string {
        $fileName = $fileNames ?? $this->options->jsonFileName;

        if (is_array($fileName)) {
            throw new \InvalidArgumentException('jsonFileName must be a string');
        }

        return $fileName;
    }
}
