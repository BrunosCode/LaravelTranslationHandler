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
        protected TranslationOptions $options
    ) {
    }

    public function get(?string $path = null, null|string|array $fileNames = null): TranslationCollection
    {
        $filePath = $this->getFilePath($path, $fileNames);

        $rawTranslations = $this->read($filePath);

        return $this->buildTranslations($rawTranslations);
    }

    public function put(TranslationCollection $translations, ?string $path = null, null|string|array $fileNames = null): int
    {
        $filePath = $this->getFilePath($path, $fileNames);

        $rawTranslations = $this->buildForFile($translations, $this->options->locales);

        if (! $this->write($rawTranslations, $filePath)) {
            return 0;
        }

        return $translations->count();
    }

    public function delete(?string $path = null, null|string|array $fileNames = null): int
    {
        $counter = $this->get($path, $fileNames)->count();
        $filePath = $this->getFilePath($path, $fileNames);

        if (! File::exists($filePath)) {
            return 0;
        }

        if (! File::delete($filePath)) {
            return 0;
        }

        return $counter;
    }

    protected function buildTranslations(array $rawTranslations): TranslationCollection
    {
        $translations = new TranslationCollection();

        foreach ($rawTranslations as $row) {
            $key = $row['key'];
            foreach ($this->options->locales as $locale) {
                $value = $row[$locale];
                $translations->push(new Translation($key, $locale, $value));
            }
        }

        return $translations;
    }

    protected function read(string $filePath): array
    {
        if (empty($filePath)) {
            throw new \InvalidArgumentException('filePath cannot be empty');
        }

        if (! File::exists($filePath)) {
            return [];
        }

        $handler = fopen($filePath, 'r');

        $headers = fgetcsv($handler, 0, $this->options->csvDelimiter);

        $rawTranslations = [];

        while ($data = fgetcsv($handler, 0, $this->options->csvDelimiter)) {
            if (count($data) <= 1 || count($data) != count($headers)) {
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

    protected function buildForFile(TranslationCollection $translations, array $locales): array
    {
        $fileTranslations = [];
        $orderedHeaders = ['key', ...$locales];

        foreach ($translations as $translation) {
            $fileTranslations[$translation->key] ??= ['key' => $translation->key];
            $fileTranslations[$translation->key][$translation->locale] = $translation->value;
        }

        foreach ($fileTranslations as $key => $value) {
            foreach ($locales as $locale) {
                if (! array_key_exists($locale, $value)) {
                    $fileTranslations[$key][$locale] = '';
                }
            }
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

    protected function write(array $translations, string $filePath): bool
    {
        if (empty($filePath)) {
            throw new \InvalidArgumentException('filePath cannot be empty');
        }

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

    protected function getFilePath(
        string $path,
        string $fileName
    ): string {
        if (empty($path)) {
            throw new \InvalidArgumentException('csvPath cannot be empty');
        }
        if (empty($fileName)) {
            throw new \InvalidArgumentException('csvFileName cannot be empty');
        }
        return "{$path}/{$fileName}.csv";
    }

    protected function getPath(?string $path): string
    {
        $path ??= $this->options->csvPath;
        if (empty($path)) {
            throw new \InvalidArgumentException('csvPath cannot be empty');
        }

        return $path;
    }

    protected function getFileName(
        null|string|array $fileNames
    ): string {
        $fileName = $fileNames ?? $this->options->csvFileName;

        if (is_array($fileName)) {
            throw new \InvalidArgumentException('csvFileName must be a string');
        }

        return $fileName;
    }
}
