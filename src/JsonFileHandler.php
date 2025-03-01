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
        private TranslationOptions $options
    ) {}

    public function get(?string $path = null): TranslationCollection
    {
        $translations = new TranslationCollection;

        foreach ($this->options->locales as $locale) {
            $rawTranslations = $this->read($path, $locale);

            $translations->addTranslations($this->build($locale, $rawTranslations));
        }

        return $translations;
    }

    private function build(string $locale, array $rawTranslations): TranslationCollection
    {
        $translations = new TranslationCollection;

        if ($this->options->jsonNested) {
            return $this->buildFromNestedArray($translations, '', $locale, $rawTranslations);
        }

        return $this->buildFromFlatArray($translations, $locale, $rawTranslations);
    }

    private function buildFromFlatArray(TranslationCollection $translations, string $locale, array $rawTranslations)
    {
        foreach ($rawTranslations as $key => $value) {
            $translations->addTranslation(new Translation($key, $locale, $value));
        }

        return $translations;
    }

    private function buildFromNestedArray(TranslationCollection $translations, string $key, string $locale, string|array &$value): TranslationCollection
    {
        if (is_array($value)) {
            foreach ($value as $childKey => $childValue) {
                $currentKey = $key ? $key.$this->options->keyDelimiter.$childKey : $childKey;
                $translations = $this->buildFromNestedArray($translations, $currentKey, $locale, $childValue);
            }
        } else {
            $translations->addTranslation(new Translation($key, $locale, $value));
        }

        return $translations;
    }

    private function read(?string $path, string $locale): array
    {
        $filePath = $this->getFilePath($path, $locale);

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

    public function put(TranslationCollection $translations, ?string $path = null): int
    {
        $counter = 0;

        foreach ($this->options->locales as $locale) {
            $filteredTranslations = $translations->whereLocale($locale);

            if ($filteredTranslations->isEmpty()) {
                continue;
            }

            $rawTranslations = $this->buildForFile($filteredTranslations, $locale);

            // $currentRawTranslations = $this->read($path, $locale);

            // $rawTranslations = array_replace_recursive($currentRawTranslations, $rawTranslations);

            $this->write($rawTranslations, $path, $locale);

            $counter += $filteredTranslations->count();
        }

        return $counter;
    }

    protected function buildForFile(TranslationCollection $translations, string $locale): array
    {
        $fileTranslations = [];

        if ($this->options->jsonNested) {
            return $this->buildForNestedFile($fileTranslations, $locale, $translations);
        }

        return $this->buildForFlatFile($fileTranslations, $locale, $translations);
    }

    private function buildForFlatFile(array $fileTranslations, string $locale, TranslationCollection $translations): array
    {
        foreach ($translations as $translation) {
            if ($translation->locale != $locale) {
                continue;
            }

            $fileTranslations[$translation->key] = $translation->value;
        }

        return $fileTranslations;
    }

    private function buildForNestedFile(array $fileTranslations, string $locale, TranslationCollection $translations): array
    {
        foreach ($translations as $translation) {
            if ($translation->locale !== $locale) {
                continue;
            }

            $keys = explode($this->options->keyDelimiter, $translation->key);

            $current = &$fileTranslations;

            foreach ($keys as $key) {
                $current = &$current[$key];
            }

            $current = $translation->value;
        }

        return $fileTranslations;
    }

    protected function write(array $translations, ?string $path, string $locale): bool
    {
        $filePath = $this->getFilePath($path, $locale);

        if (! File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0777, true);
        }

        return (bool) File::put(
            $filePath,
            json_encode($translations, $this->options->jsonFormat ? JSON_PRETTY_PRINT : 0),
            false
        );
    }

    public function delete(?string $path = null): int
    {
        $currentTranslations = $this->get($path);

        foreach ($this->options->locales as $locale) {
            if (! File::delete($this->getFilePath($path, $locale))) {
                return 0;
            }
        }

        return $currentTranslations->count();
    }

    public function getFilePath(?string $path, string $locale): string
    {
        if (is_string($path) && empty($path)) {
            throw new \InvalidArgumentException('Path cannot be empty');
        }

        $path ??= $this->options->jsonPath;

        $fileName = $this->options->jsonFileName;
        if (empty($fileName)) {
            return "{$path}/{$locale}.json";
        }

        return "{$path}/{$locale}/{$fileName}.json";
    }
}
