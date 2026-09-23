<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Concerns\ComparesTranslations;
use BrunosCode\TranslationHandler\Concerns\IdentifiesManagedKeys;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface;
use Illuminate\Support\Facades\File;

class CsvFileHandler implements FileHandlerInterface
{
    use ComparesTranslations, IdentifiesManagedKeys;

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
                // A locale column missing from the header means "no value here",
                // not a malformed file: skip it instead of raising an undefined index.
                if (! array_key_exists($locale, $row)) {
                    continue;
                }

                $translations->push(new Translation($key, $locale, $row[$locale]));
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

        $headers = fgetcsv($handler, 0, $this->options->csvDelimiter, escape: '\\');

        $rawTranslations = [];

        $line = 1;

        while ($data = fgetcsv($handler, 0, $this->options->csvDelimiter, escape: '\\')) {
            $line++;

            if (count($data) <= 1) {
                throw new \InvalidArgumentException("Invalid CSV at line {$line}: expected at least 2 columns, got ".count($data).'. Check that the delimiter is "'.$this->options->csvDelimiter.'"');
            }

            if (count($data) != count($headers)) {
                throw new \InvalidArgumentException("Invalid CSV at line {$line}: expected ".count($headers).' columns (matching headers), got '.count($data));
            }

            $rawTranslations[$data[0]] = array_combine($headers, $data);
        }

        fclose($handler);

        return $rawTranslations;
    }

    public function put(TranslationCollection $translations, ?string $path = null): int
    {
        $existing = $this->read($path);

        // The CSV is a single shared file: rows of other groups and columns of
        // locales not configured right now must survive the write.
        $headers = $this->mergeHeaders($existing);

        $rawTranslations = $this->mergeWithUnmanaged($existing, $this->buildForFile($translations), $headers);

        if ($this->rawTranslationsEqual($existing, $rawTranslations)) {
            return 0;
        }

        if (empty($rawTranslations)) {
            File::delete($this->getFilePath($path));

            return $this->countRawDifferences($this->stripCsvKeyField($existing), []);
        }

        if (! $this->write($rawTranslations, $path, $headers)) {
            return 0;
        }

        return $this->countRawDifferences(
            $this->stripCsvKeyField($existing),
            $this->stripCsvKeyField($rawTranslations)
        );
    }

    /**
     * Header of the file to write: the existing columns in their original
     * order, plus any configured locale not yet present.
     *
     * @param  array<string, array<string, string|null>>  $existing
     * @return string[]
     */
    private function mergeHeaders(array $existing): array
    {
        $existingHeaders = $existing === [] ? [] : array_keys(reset($existing));

        return array_values(array_unique(['key', ...$existingHeaders, ...$this->options->locales]));
    }

    /**
     * Managed rows come first, in the order of the collection, each keeping
     * the columns of unmanaged locales from the existing row; unmanaged rows
     * follow in their original order. Managed rows missing from the
     * collection are dropped. Every row is normalised to the given headers.
     *
     * @param  string[]  $headers
     */
    private function mergeWithUnmanaged(array $existing, array $managed, array $headers): array
    {
        $result = [];

        foreach ($managed as $key => $row) {
            $result[$key] = array_replace($existing[$key] ?? [], $row);
        }

        foreach ($existing as $key => $row) {
            if (isset($result[$key]) || $this->isManagedKey((string) $key)) {
                continue;
            }

            $result[$key] = $row;
        }

        foreach ($result as $key => $row) {
            $normalised = [];

            foreach ($headers as $header) {
                $normalised[$header] = $row[$header] ?? '';
            }

            $result[$key] = $normalised;
        }

        return $result;
    }

    private function stripCsvKeyField(array $rows): array
    {
        return array_map(fn (array $row) => array_diff_key($row, ['key' => true]), $rows);
    }

    protected function buildForFile(TranslationCollection $translations): array
    {
        $fileTranslations = [];
        $orderedHeaders = ['key', ...$this->options->locales];

        foreach ($translations as $translation) {
            $fileTranslations[$translation->key] ??= ['key' => $translation->key];
            $fileTranslations[$translation->key][$translation->locale] = $translation->value;
        }

        foreach ($fileTranslations as $key => $value) {
            foreach ($this->options->locales as $locale) {
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

    /**
     * @param  string[]|null  $headers  Defaults to `key` plus the configured locales.
     */
    protected function write(array $translations, ?string $path, ?array $headers = null): bool
    {
        $filePath = $this->getFilePath($path);

        // check if folder exists
        if (! File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0777, true);
        }

        $csv = fopen($filePath, 'w');
        fputcsv($csv, $headers ?? ['key', ...$this->options->locales], $this->options->csvDelimiter, escape: '\\');

        foreach ($translations as $translation) {
            fputcsv($csv, $translation, $this->options->csvDelimiter, escape: '\\');
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
            throw new \InvalidArgumentException('CSV handler path cannot be an empty string');
        }

        $path ??= $this->options->csvPath;

        return "{$path}/{$this->options->csvFileName}.csv";
    }
}
