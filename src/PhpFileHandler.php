<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface;
use Illuminate\Support\Facades\File;
use SplFileInfo;

class PhpFileHandler implements FileHandlerInterface
{
    public function __construct(
        protected TranslationOptions $options
    ) {
    }

    public function get(
        ?string $path = null,
        null|string|array $fileNames = null
    ): TranslationCollection {
        $translations = new TranslationCollection();
        $fileNames = $this->getFileNames($path, $fileNames);

        foreach ($fileNames as $filename) {
            foreach ($this->options->locales as $locale) {
                $filePath = $this->getFilePath($path, $filename, $locale);

                $rawTranslations = $this->read($filePath);

                $translations = $this->buildTranslations(
                    $translations,
                    $rawTranslations,
                    $filename,
                    $locale,
                    $this->options->keyDelimiter
                );
            }
        }

        return $translations;
    }

    public function put(
        TranslationCollection $translations,
        ?string $path = null,
        null|string|array $fileNames = null
    ): int {
        $counter = 0;
        $fileNames = $this->getFileNames($path, $fileNames);

        foreach ($fileNames as $filename) {
            foreach ($this->options->locales as $locale) {
                $filteredTranslations = $translations
                    ->clone()
                    ->whereGroup($filename)
                    ->whereLocale($locale);

                if ($filteredTranslations->isEmpty()) {
                    continue;
                }

                $rawTranslations = $this->buildForFile(
                    $filteredTranslations,
                    $filename,
                    $locale,
                    $this->options->keyDelimiter
                );

                $filePath = $this->getFilePath($path, $filename, $locale);

                $this->write($rawTranslations, $filePath, $this->options->phpFormat);

                $counter += $filteredTranslations->count();
            }
        }

        return $counter;
    }

    public function delete(
        ?string $path = null,
        null|string|array $fileNames = null
    ): int {
        $fileNames = $this->getFileNames($path, $fileNames);
        $counter = $this->get($path, $fileNames)->count();

        foreach ($fileNames as $filename) {
            foreach ($this->options->locales as $locale) {
                File::delete($this->getFilePath($path, $filename, $locale));
            }
        }

        return $counter;
    }

    protected function buildTranslations(
        TranslationCollection $translations,
        string|array &$value,
        string $key,
        string $locale,
        string $keyDelimiter
    ): TranslationCollection {
        if (is_array($value)) {
            foreach ($value as $childKey => $childValue) {
                $currentKey = $key ? $key.$keyDelimiter.$childKey : $childKey;
                $translations = $this->buildTranslations(
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

        $rawTranslations = include $filePath;

        return is_array($rawTranslations) ? $rawTranslations : [];
    }

    protected function buildForFile(
        TranslationCollection $translations,
        string $filename,
        string $locale,
        string $keyDelimiter
    ): array {
        $fileTranslations = [];

        foreach ($translations as $translation) {
            if ($translation->locale !== $locale) {
                continue;
            }

            $keys = explode($keyDelimiter, $translation->key);

            if ($keys[0] === $filename) {
                unset($keys[0]);
            }

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
            '<?php return '.$this->exportPhp($translations, $format).';'
        );
    }

    protected function exportPhp(
        array $translations,
        bool $format = false
    ): string {
        $export = var_export($translations, true);

        if (! $format) {
            return $export;
        }

        $patterns = [
            "/array \(/" => '[',
            "/^([ ]*)\)(,?)$/m" => '$1]$2',
        ];

        $formattedExport = preg_replace(array_keys($patterns), array_values($patterns), $export);

        if ($formattedExport === null) {
            throw new \RuntimeException('preg_replace failed');
        }

        return $formattedExport;
    }

    protected function getPath(
        ?string $path
    ): string {
        $path ??= $this->options->phpPath;

        if (empty($path)) {
            throw new \InvalidArgumentException('phpPath cannot be empty');
        }

        return $path;
    }

    protected function getFilePath(
        ?string $path,
        string $filename,
        string $locale
    ): string {
        $path = $this->getPath($path);

        return "{$path}/{$locale}/{$filename}.php";
    }

    protected function getFileNames(
        ?string $path,
        null|string|array $fileNames
    ): array {
        $fileNames ??= $this->options->phpFileNames;

        if (is_string($fileNames)) {
            throw new \InvalidArgumentException('phpFileNames must be an array');
        }

        if (empty($fileNames)) {
            $fileNames = collect(File::allFiles($this->getPath($path)))
                ->map(fn (SplFileInfo $file) => $file->getFilename())
                ->unique()
                ->toArray();
        }

        return $fileNames;
    }
}
