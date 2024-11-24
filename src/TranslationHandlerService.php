<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Interfaces\DatabaseHandlerInterface;
use BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface;

class TranslationHandlerService
{
    protected TranslationOptions $defaultOptions;

    protected ?TranslationOptions $options;

    public function __construct()
    {
        $this->defaultOptions = new TranslationOptions;
        $this->options = null;
    }

    public function import(?string $from = null, ?string $to = null, bool $force = false, ?string $fromPath = null, ?string $toPath = null): bool
    {
        $options = $this->getOptions();

        $from = $from ?? $options->defaultImportFrom;

        $to = $to ?? $options->defaultImportTo;

        $translations = $this->get($from, $fromPath);

        if ($translations->isEmpty()) {
            throw new \Error('No translations were found');
        }

        return $this->set($translations, $to, $toPath, $force) > 0;
    }

    public function export(?string $from = null, ?string $to = null, bool $force = false, ?string $fromPath = null, ?string $toPath = null): bool
    {
        $options = $this->getOptions();

        $from = $from ?? $options->defaultExportFrom;

        $to = $to ?? $options->defaultExportTo;

        $translations = $this->get($from, $fromPath);

        if ($translations->isEmpty()) {
            throw new \Error('No translations were found');
        }

        return $this->set($translations, $to, $toPath, $force) > 0;
    }

    public function get(string $from, ?string $path = null): TranslationCollection
    {
        $options = $this->getOptions();

        $translations = match ($from) {
            TranslationOptions::PHP => $this->getPhpHandler()->get(
                path: $path
            ),
            TranslationOptions::CSV => $this->getCsvHandler()->get(
                path: $path
            ),
            TranslationOptions::JSON => $this->getJsonHandler()->get(
                path: $path
            ),
            TranslationOptions::DB => $this->getDbHandler()->get(
                connection: $path
            ),
            default => throw new \InvalidArgumentException('Invalid $from type'),
        };

        return $translations
            ->whereGroupIn($options->fileNames)
            ->whereLocaleIn($options->locales);
    }

    public function set(TranslationCollection $translations, string $to, ?string $path = null, bool $force = false): int
    {
        $oldTranslations = $this->get($to, $path);

        if ($force) {
            $newTranslations = $oldTranslations->replaceTranslations($translations);
        } else {
            $newTranslations = $oldTranslations->addTranslations($translations);
        }

        $newTranslations = $newTranslations->sortTranslations();

        return match ($to) {
            TranslationOptions::PHP => $this->getPhpHandler()->put(
                translations: $newTranslations,
                path: $path,
            ),
            TranslationOptions::CSV => $this->getCsvHandler()->put(
                translations: $newTranslations,
                path: $path,
            ),
            TranslationOptions::JSON => $this->getJsonHandler()->put(
                translations: $newTranslations,
                path: $path,
            ),
            TranslationOptions::DB => $this->getDbHandler()->put(
                translations: $newTranslations,
                connection: $path
            ),
            default => throw new \InvalidArgumentException('Invalid $to type'),
        };
    }

    public function delete(string $from, ?string $path = null): int
    {
        return match ($from) {
            TranslationOptions::PHP => $this->getPhpHandler()->delete(
                path: $path
            ),
            TranslationOptions::CSV => $this->getCsvHandler()->delete(
                path: $path
            ),
            TranslationOptions::JSON => $this->getJsonHandler()->delete(
                path: $path
            ),
            TranslationOptions::DB => $this->getDbHandler()->delete(
                connection: $path
            ),
            default => throw new \InvalidArgumentException('Invalid $from type'),
        };
    }

    public function getTypes(): array
    {
        return TranslationOptions::TYPES;
    }

    public function getPhpHandler(): FileHandlerInterface
    {
        return app($this->getOptions()->phpHandlerClass, [$this->getOptions()]);
    }

    public function getCsvHandler(): FileHandlerInterface
    {
        return app($this->getOptions()->csvHandlerClass, [$this->getOptions()]);
    }

    public function getJsonHandler(): FileHandlerInterface
    {
        return app($this->getOptions()->jsonHandlerClass, [$this->getOptions()]);
    }

    public function getDbHandler(): DatabaseHandlerInterface
    {
        return app($this->getOptions()->dbHandlerClass, [$this->getOptions()]);
    }

    public function getOptions(): TranslationOptions
    {
        return $this->options ?? $this->defaultOptions;
    }

    public function setOptions(TranslationOptions $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function resetOptions(): self
    {
        $this->options = null;

        return $this;
    }

    public function setOption(string $name, mixed $value): self
    {
        if ($this->options === null) {
            $this->options = clone $this->defaultOptions;
        }

        $this->options->$name = $value;

        return $this;
    }

    public function getOption(string $name): mixed
    {
        return $this->getOptions()->$name;
    }

    public function getDefaultOptions(): TranslationOptions
    {
        return $this->defaultOptions;
    }

    public function setDefaultOptions(TranslationOptions $options): self
    {
        $this->defaultOptions = $options;

        return $this;
    }

    public function setDefaultOption(string $name, mixed $value): self
    {
        $this->defaultOptions->$name = $value;

        return $this;
    }

    public function getDefaultOption(string $name): mixed
    {
        return $this->defaultOptions->$name;
    }
}
