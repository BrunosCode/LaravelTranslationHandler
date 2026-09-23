<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Concerns\ComparesTranslations;
use BrunosCode\TranslationHandler\Concerns\IdentifiesManagedKeys;
use BrunosCode\TranslationHandler\Concerns\NormalizesRawValues;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface;
use Illuminate\Support\Facades\File;

class JsonFileHandler implements FileHandlerInterface
{
    use ComparesTranslations, IdentifiesManagedKeys, NormalizesRawValues;

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

        // if ($this->options->jsonNested) {
        //     return $this->buildFromFlatArray($translations, $locale, $rawTranslations);
        // }

        return $this->buildFromNestedArray($translations, '', $locale, $rawTranslations);
    }

    // private function buildFromFlatArray(TranslationCollection $translations, string $locale, array $rawTranslations)
    // {
    //     foreach ($rawTranslations as $key => $value) {
    //         $translations->addTranslation(new Translation($key, $locale, $value));
    //     }

    //     return $translations;
    // }

    private function buildFromNestedArray(TranslationCollection $translations, string $key, string $locale, mixed $value): TranslationCollection
    {
        if (is_array($value)) {
            foreach ($value as $childKey => $childValue) {
                $currentKey = $key ? $key.$this->options->keyDelimiter.$childKey : (string) $childKey;
                $translations = $this->buildFromNestedArray($translations, $currentKey, $locale, $childValue);
            }
        } else {
            $translations->addTranslation(new Translation($key, $locale, $this->normalizeRawValue($value, $key)));
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

            $existing = $this->read($path, $locale);

            // The locale file is shared with keys this handler does not manage
            // (other groups, or plain sentence keys): replace only the managed
            // part and carry the rest over untouched.
            $rawTranslations = $this->mergeWithUnmanaged(
                $existing,
                $this->buildForFile($filteredTranslations, $locale),
            );

            if ($this->rawTranslationsEqual($existing, $rawTranslations)) {
                continue;
            }

            if (empty($rawTranslations)) {
                File::delete($this->getFilePath($path, $locale));
                $counter += $this->countRawDifferences($existing, []);

                continue;
            }

            $this->write($rawTranslations, $path, $locale);

            $counter += $this->countRawDifferences($existing, $rawTranslations);
        }

        return $counter;
    }

    /**
     * Managed entries come first, in the order of the collection (so sorting
     * is honoured); unmanaged entries of the existing file follow, in their
     * original order. Managed entries missing from the collection are dropped.
     */
    private function mergeWithUnmanaged(array $existing, array $managed): array
    {
        $unmanaged = [];

        foreach ($existing as $key => $value) {
            // The reader accepts both flat ("group.key") and nested ({"group": {...}})
            // entries whatever jsonNested says, so both forms count as managed here.
            if (! $this->isManagedKey((string) $key) && ! $this->isManagedGroup((string) $key)) {
                $unmanaged[$key] = $value;
            }
        }

        // Array union rather than array_merge: numeric-looking keys must not be renumbered.
        return $managed + $unmanaged;
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
        $translations->assertNoParentLeafConflicts($this->options->keyDelimiter);

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

        // Encode before touching the file: on invalid UTF-8 json_encode used to
        // return false and File::put() wrote an empty file, wiping the locale.
        $json = json_encode(
            $translations,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | ($this->options->jsonFormat ? JSON_PRETTY_PRINT : 0)
        );

        return (bool) File::put($filePath, $json, false);
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
            throw new \InvalidArgumentException('JSON handler path cannot be an empty string');
        }

        $path ??= $this->options->jsonPath;

        $fileName = $this->options->jsonFileName;
        if (empty($fileName)) {
            return "{$path}/{$locale}.json";
        }

        return "{$path}/{$locale}/{$fileName}.json";
    }
}
