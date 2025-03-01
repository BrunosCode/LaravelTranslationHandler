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

    public function move(
        bool $overwrite,
        string $fromType,
        string $toType,
        ?string $fromPath = null,
        ?string $toPath = null,
        null|string|array $fromFileNames = null,
        null|string|array $toFileNames = null
    ): bool {
        $translations = $this->get(
            type: $fromType,
            path: $fromPath,
            fileNames: $fromFileNames
        );

        return $this->set(
            translations: $translations,
            type: $toType,
            path: $toPath,
            fileNames: $toFileNames,
            overwrite: $overwrite
        ) > 0;
    }

    public function get(
        string $type,
        ?string $path = null,
        null|string|array $fileNames = null
    ): TranslationCollection {
        $options = $this->getOptions();

        $translations = match ($type) {
            TranslationOptions::PHP => $this->getPhpHandler()->get(
                path: $path,
                fileNames: $fileNames
            ),
            TranslationOptions::CSV => $this->getCsvHandler()->get(
                path: $path,
                fileNames: $fileNames
            ),
            TranslationOptions::JSON => $this->getJsonHandler()->get(
                path: $path,
                fileNames: $fileNames
            ),
            TranslationOptions::DB => $this->getDbHandler()->get(
                connection: $path,
            ),
            default => throw new \InvalidArgumentException('Invalid $fromType'),
        };

        return $translations
            ->whereLocaleIn($options->locales);
    }

    public function set(
        TranslationCollection $translations,
        string $type,
        ?string $path = null,
        null|string|array $fileNames = null,
        bool $overwrite = false
    ): int {
        $oldTranslations = $this->get($type, $path, $fileNames);

        if ($overwrite) {
            $newTranslations = $oldTranslations->replaceTranslations($translations);
        } else {
            $newTranslations = $oldTranslations->addTranslations($translations);
        }

        $newTranslations = $newTranslations->sortTranslations();

        return match ($type) {
            TranslationOptions::PHP => $this->getPhpHandler()->put(
                translations: $newTranslations,
                path: $path,
                fileNames: $fileNames
            ),
            TranslationOptions::CSV => $this->getCsvHandler()->put(
                translations: $newTranslations,
                path: $path,
                fileNames: $fileNames
            ),
            TranslationOptions::JSON => $this->getJsonHandler()->put(
                translations: $newTranslations,
                path: $path,
                fileNames: $fileNames
            ),
            TranslationOptions::DB => $this->getDbHandler()->put(
                translations: $newTranslations,
                connection: $path
            ),
            default => throw new \InvalidArgumentException('Invalid $toType'),
        };
    }

    public function delete(string $type, ?string $path = null, null|string|array $fileNames = null): int
    {
        return match ($type) {
            TranslationOptions::PHP => $this->getPhpHandler()->delete(
                path: $path,
                fileNames: $fileNames
            ),
            TranslationOptions::CSV => $this->getCsvHandler()->delete(
                path: $path,
                fileNames: $fileNames
            ),
            TranslationOptions::JSON => $this->getJsonHandler()->delete(
                path: $path,
                fileNames: $fileNames
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
