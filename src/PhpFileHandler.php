<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Concerns\ComparesTranslations;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class PhpFileHandler implements FileHandlerInterface
{
    use ComparesTranslations;

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

        $writtenPaths = [];

        foreach ($this->options->fileNames as $filename) {
            foreach ($this->options->locales as $locale) {
                $filteredTranslations = $translations
                    ->clone()
                    ->whereGroup($filename)
                    ->whereLocale($locale);

                $existing = $this->read($path, $filename, $locale);

                if ($filteredTranslations->isEmpty()) {
                    if (! empty($existing)) {
                        File::delete($this->getFilePath($path, $filename, $locale));
                        $counter += $this->countRawDifferences($existing, []);
                    }

                    continue;
                }

                $rawTranslations = $this->buildForFile($filteredTranslations, $filename, $locale);

                if ($this->rawTranslationsEqual($existing, $rawTranslations)) {
                    continue;
                }

                $this->write($rawTranslations, $path, $filename, $locale);

                $writtenPaths[] = $this->getFilePath($path, $filename, $locale);

                $counter += $this->countRawDifferences($existing, $rawTranslations);
            }
        }

        if ($this->options->phpPint) {
            $this->formatWithPint($writtenPaths);
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
            throw new \RuntimeException('Failed to format PHP export: preg_replace returned null (error code: '.preg_last_error().')');
        }

        return $formattedExport;
    }

    /**
     * Format the given PHP files with Pint, using the host project's code style.
     *
     * The Pint binary is resolved from the host project first, then from this
     * package's own vendor (only present while developing the package). When no
     * binary is found the step is skipped silently — the files keep their raw
     * var_export / phpFormat output.
     *
     * @param  string[]  $paths
     */
    protected function formatWithPint(array $paths): void
    {
        if (empty($paths)) {
            return;
        }

        $binary = $this->resolvePintBinary();

        if ($binary === null) {
            return;
        }

        // Run via PHP_BINARY so the call works cross-platform (the bare binary
        // is not directly executable on Windows), and from the project root so
        // Pint discovers the host project's pint.json.
        Process::path(base_path())->run([PHP_BINARY, $binary, ...$paths]);
    }

    protected function resolvePintBinary(): ?string
    {
        $candidates = [
            base_path('vendor/bin/pint'),
            dirname(__DIR__).'/vendor/bin/pint',
        ];

        foreach ($candidates as $binary) {
            if (is_file($binary)) {
                return $binary;
            }
        }

        return null;
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
            throw new \InvalidArgumentException('PHP handler path cannot be an empty string');
        }

        $path ??= $this->options->phpPath;

        return "{$path}/{$locale}/{$filename}.php";
    }
}
