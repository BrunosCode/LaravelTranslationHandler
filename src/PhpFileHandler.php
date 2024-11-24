<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface;
use Illuminate\Support\Facades\File;

class PhpFileHandler implements FileHandlerInterface
{
    public function __construct(
        private TranslationOptions $options
    ) {}

    public function get(?string $path = null): TranslationCollection
    {
        $translations = new TranslationCollection;

        foreach ($this->options->fileNames as $filename) {
            foreach ($this->options->locales as $locale) {
                $rawTranslations = $this->read($path, $filename, $locale);

                $translations = $this->build($translations, $filename, $locale, $rawTranslations);
            }
        }

        return $translations;
    }

    private function build(TranslationCollection $translations, string $key, string $locale, string|array &$value): TranslationCollection
    {
        if (is_array($value)) {
            foreach ($value as $childKey => $childValue) {
                $currentKey = $key ? $key.$this->options->keyDelimiter.$childKey : $childKey;
                $translations = $this->build($translations, $currentKey, $locale, $childValue);
            }
        } else {
            $translations->addTranslation(new Translation($key, $locale, $value));
        }

        return $translations;
    }

    private function read(?string $path, string $filename, string $locale): array
    {
        $filePath = $this->getFilePath($path, $filename, $locale);

        if (! File::exists($filePath)) {
            return [];
        }

        $rawTranslations = include $filePath;

        return is_array($rawTranslations) ? $rawTranslations : [];
    }

    public function put(TranslationCollection $translations, ?string $path = null): int
    {
        $counter = 0;

        foreach ($this->options->fileNames as $filename) {
            foreach ($this->options->locales as $locale) {
                $filteredTranslations = $translations
                    ->clone()
                    ->whereGroup($filename)
                    ->whereLocale($locale);

                if ($filteredTranslations->isEmpty()) {
                    continue;
                }

                $rawTranslations = $this->buildForFile($filteredTranslations, $filename, $locale);

                $currentRawTranslations = $this->read($path, $filename, $locale);

                $rawTranslations = array_replace_recursive($currentRawTranslations, $rawTranslations);

                $this->write($rawTranslations, $path, $filename, $locale);

                $counter += $filteredTranslations->count();
            }
        }

        return $counter;
    }

    protected function buildForFile(TranslationCollection $translations, string $filename, string $locale): array
    {
        $fileTranslations = [];

        foreach ($translations as $translation) {
            if ($translation->locale !== $locale) {
                continue;
            }

            $keys = explode($this->options->keyDelimiter, $translation->key);

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

    protected function write(array $translations, ?string $path, string $filename, string $locale): bool
    {
        $filePath = $this->getFilePath($path, $filename, $locale);

        if (! File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0777, true);
        }

        return (bool) File::put(
            $filePath,
            '<?php return '.$this->exportPhp($translations).';'
        );
    }

    protected function exportPhp(array $translations): string
    {
        $export = var_export($translations, true);

        if (! $this->options->phpFormat) {
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

    public function delete(?string $path = null): int
    {
        $counter = $this->get($path)->count();

        foreach ($this->options->fileNames as $filename) {
            foreach ($this->options->locales as $locale) {
                File::delete($this->getFilePath($path, $filename, $locale));
            }
        }

        return $counter;
    }

    public function getFilePath(?string $path, string $filename, string $locale): string
    {
        if (is_string($path) && empty($path)) {
            throw new \InvalidArgumentException('Path cannot be empty');
        }

        $path ??= $this->options->phpPath;

        return "{$path}/{$locale}/{$filename}.php";
    }
}
