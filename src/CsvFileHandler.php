<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface;
use Illuminate\Support\Facades\File;

class CsvFileHandler implements FileHandlerInterface
{
    public function __construct(
        private TranslationOptions $options
    ) {}

    public function get(?string $path = null): TranslationCollection
    {
        $rawTranslations = $this->read($path);

        return $this->build($rawTranslations);
    }

    private function build(array $rawTranslations): TranslationCollection
    {
        $translations = new TranslationCollection;

        foreach ($rawTranslations as $row) {
            $key = $row['key'];
            foreach ($this->options->locales as $locale) {
                $value = $row[$locale];
                $translations->push(new Translation($key, $locale, $value));
            }
        }

        return $translations;
    }

    private function read(?string $path): array
    {
        $filePath = $this->getFilePath($path);

        if (! File::exists($filePath)) {
            return [];
        }

        $handler = fopen($filePath, 'r');

        $headers = fgetcsv($handler, 0, $this->options->csvDelimiter);

        $rawTranslations = [];

        while ($data = fgetcsv($handler, 0, $this->options->csvDelimiter)) {
            if (count($data) <= 1) {
                throw new \InvalidArgumentException('Invalid CSV file');
            }

            $rawTranslations[$data[0]] = array_combine($headers, $data);
        }

        fclose($handler);

        if (! is_array($rawTranslations)) {
            return [];
        }

        return $rawTranslations;
    }

    public function put(TranslationCollection $translations, ?string $path = null): int
    {
        $rawTranslations = $this->buildForFile($translations);

        $currentRawTranslations = $this->read($path);

        $rawTranslations = array_replace_recursive($currentRawTranslations, $rawTranslations);

        if (! $this->write($rawTranslations, $path)) {
            return 0;
        }

        return $translations->count();
    }

    protected function buildForFile(TranslationCollection $translations): array
    {
        $fileTranslations = [];
        $orderedHeaders = ['key', ...$this->options->locales];

        foreach ($translations as $translation) {
            $fileTranslations[$translation->key] ??= ['key' => $translation->key];
            $fileTranslations[$translation->key][$translation->locale] = $translation->value;
        }

        $fileTranslations = array_map(function (array $array) use ($orderedHeaders) {
            $sortedArray = [];

            foreach ($orderedHeaders as $key) {
                if (array_key_exists($key, $array)) {
                    $sortedArray[$key] = $array[$key];
                }
            }

            return $sortedArray;
        }, $fileTranslations);

        return $fileTranslations;
    }

    protected function write(array $translations, ?string $path): bool
    {
        $filePath = $this->getFilePath($path);

        // check if folder exists
        if (! File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0777, true);
        }

        $csv = fopen($filePath, 'w');
        fputcsv($csv, ['key', ...$this->options->locales], $this->options->csvDelimiter);

        foreach ($translations as $translation) {
            fputcsv($csv, $translation, $this->options->csvDelimiter);
        }

        return fclose($csv);
    }

    public function delete(?string $path = null): int
    {
        $counter = $this->get($path)->count();

        if (! File::exists($this->getFilePath($path))) {
            return 0;
        }

        if (! File::delete($this->getFilePath($path))) {
            return 0;
        }

        return $counter;
    }

    public function getFilePath(?string $path = null): string
    {
        if (is_string($path) && empty($path)) {
            throw new \InvalidArgumentException('Path cannot be empty');
        }

        $path ??= $this->options->csvPath;

        return "{$path}/{$this->options->csvFileName}.csv";
    }
}
