<?php

namespace BrunosCode\TranslationHandler\Commands\Behaviors;

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;

trait HasTranslationOptions
{
    protected function getTranslationTypeOption(string $optionName, string $default, bool $ask = false): ?string
    {
        if (! $this->hasOption($optionName)) {
            throw new \InvalidArgumentException('--'.$optionName.' option is not allowed in this command: '.self::class);
        }

        $type = $this->option($optionName);

        if (empty($type) && $ask) {
            $type = $this->choice(
                'Which type of translations?',
                TranslationHandler::getTypes(),
            );
        }

        if (empty($type)) {
            $type = $default;
        }

        if (! in_array($type, TranslationHandler::getTypes())) {
            throw new \InvalidArgumentException('Invalid to type argument type: '.$type);
        }

        $this->comment('- type '.$type);

        return $type;
    }

    protected function getTranslationPathOption(string $optionName, string $type, bool $ask = false): ?string
    {
        if (! $this->hasOption($optionName)) {
            throw new \InvalidArgumentException('--'.$optionName.' option is not allowed in this command: '.self::class);
        }

        $path = $this->option($optionName);

        $default = match ($type) {
            TranslationOptions::PHP => TranslationHandler::getOptions()->phpPath,
            TranslationOptions::CSV => TranslationHandler::getOptions()->csvPath,
            TranslationOptions::JSON => TranslationHandler::getOptions()->jsonPath,
            TranslationOptions::DB => TranslationHandler::getOptions()->dbConnection,
        };

        if (empty($path) && $ask) {
            $path = $this->ask(
                'Choose a path:',
                $default
            );
        }

        if (empty($path)) {
            throw new \InvalidArgumentException('--'.$optionName.' cannot be empty');
        }

        $this->comment('- path '.$path);

        return $path ?: null;
    }

    protected function getTranslationFileNamesOption(string $optionName, string $type, bool $ask = false): array
    {
        if (! $this->hasOption($optionName)) {
            throw new \InvalidArgumentException('--'.$optionName.' option is not allowed in this command: '.self::class);
        }

        $fileNames = $this->option($optionName) ?? [];

        $default = match ($type) {
            TranslationOptions::PHP => TranslationHandler::getOptions()->phpFileNames,
            TranslationOptions::CSV => TranslationHandler::getOptions()->csvFileName,
            TranslationOptions::JSON => TranslationHandler::getOptions()->jsonFileName,
            TranslationOptions::DB => null,
        };

        if (is_array($default) && empty($fileNames) && $ask) {
            $fileNames = $this->choice(
                'Which files?',
                $default,
                implode(', ', $default),
                null,
                true
            );
        } elseif (is_string($default) && empty($fileNames) && $ask) {
            $fileNames = $this->ask(
                'Which file?',
                $default
            );
        } elseif (empty($fileNames)) {
            $fileNames = $default;
        }

        if (is_array($default) && ! is_array($fileNames)) {
            throw new \InvalidArgumentException('--'.$optionName.' option must be an array');
        } elseif (is_string($default) && ! is_string($fileNames)) {
            throw new \InvalidArgumentException('--'.$optionName.' option must be a string');
        } elseif ($default === null && empty($fileNames)) {
            throw new \InvalidArgumentException('--'.$optionName.' option cannot be empty');
        }

        if (! empty($fileNames)) {
            $this->comment('- files:'.PHP_EOL.implode(PHP_EOL.'   ', $fileNames));
        }

        return $fileNames;
    }

    protected function getTranslationLocalesOption(string $optionName, bool $ask = false): array
    {
        if (! $this->hasOption($optionName)) {
            throw new \InvalidArgumentException('--'.$optionName.' option is not allowed in this command: '.self::class);
        }

        $locales = $this->option($optionName) ?? [];

        if (empty($locales) && $ask) {
            $locales = $this->choice(
                'Select locales?',
                TranslationHandler::getOptions()->locales,
                implode(', ', TranslationHandler::getOptions()->locales),
                null,
                true
            );
        }

        if (! is_array($locales)) {
            throw new \InvalidArgumentException('Invalid '.$optionName.' option, must be an array');
        }

        if (empty($locales)) {
            $locales = TranslationHandler::getOptions()->locales;
        }
        $this->comment('- locales:'.PHP_EOL.implode(PHP_EOL.'   ', $locales));

        return $locales;
    }

    protected function getTranslationForceOption(string $optionName, bool $ask = false): bool
    {
        if (! $this->hasOption($optionName)) {
            throw new \InvalidArgumentException('--'.$optionName.' option is not allowed in this command: '.self::class);
        }

        $force = $this->option($optionName) ?? false;

        if (! $force && $ask) {
            $force = $this->confirm(
                'Do you want to overwrite the existing translations?',
                false
            );
        }

        if (! is_bool($force)) {
            throw new \InvalidArgumentException('Invalid '.$optionName.' option, must be a boolean');
        }

        if ($force) {
            $this->comment('Overwriting existing translations');
        }

        return $force;
    }

    protected function getTranslationGuidedOption(string $optionName, bool $ask = false): bool
    {
        if (! $this->hasOption($optionName)) {
            throw new \InvalidArgumentException('--'.$optionName.' option is not allowed in this command: '.self::class);
        }

        return $this->option($optionName);
    }
}
