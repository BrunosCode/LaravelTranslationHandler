<?php

namespace BrunosCode\TranslationHandler\Commands\Behaviors;

use BrunosCode\TranslationHandler\Facades\TranslationHandler;

trait HasTranslationOptions
{
    protected function getTranslationFromOption(bool $ask = false): ?string
    {
        if (! str_contains($this->signature, '--from')) {
            throw new \InvalidArgumentException('--from option is not allowed in this command: '.self::class);
        }

        $from = $this->option('from');

        if (empty($from) && $ask) {
            $from = $this->choice(
                'From where do you want to import translations?',
                TranslationHandler::getTypes(),
            );
        } elseif (empty($from)) {
            return null;
        }

        $this->comment('Exporting translations from '.$from);

        return $from;
    }

    protected function getTranslationToOption(bool $ask = false): ?string
    {
        if (! str_contains($this->signature, '--to')) {
            throw new \InvalidArgumentException('--to option is not allowed in this command: '.self::class);
        }

        $to = $this->option('to');

        if (empty($to) && $ask) {
            $to = $this->choice(
                'To where do you want to export translations?',
                TranslationHandler::getTypes(),
            );
        } elseif (empty($to)) {
            return null;
        }

        $this->comment('Exporting translations to '.$to);

        return $to;
    }

    protected function getTranslationFromPathOption(bool $ask = false): ?string
    {
        if (! str_contains($this->signature, '--from-path')) {
            throw new \InvalidArgumentException('--from-path option is not allowed in this command: '.self::class);
        }

        $fromPath = $this->option('from-path');

        if (empty($fromPath) && $ask) {
            $fromPath = $this->ask(
                'From which path do you want to import translations?',
            );
        } elseif (empty($fromPath)) {
            return null;
        }

        if (! empty($fromPath)) {
            $this->comment('Importing translations from path '.$fromPath);
        }

        return $fromPath;
    }

    protected function getTranslationToPathOption(bool $ask = false): ?string
    {
        if (! str_contains($this->signature, '--to-path')) {
            throw new \InvalidArgumentException('--to-path option is not allowed in this command: '.self::class);
        }

        $toPath = $this->option('to-path');

        if (empty($toPath) && $ask) {
            $toPath = $this->ask(
                'To which path do you want to export translations?',
            );
        } elseif (empty($toPath)) {
            return null;
        }

        if (! empty($toPath)) {
            $this->comment('Exporting translations to path '.$toPath);
        }

        return $toPath;
    }

    protected function getTranslationFileNamesOption(bool $ask = false): array
    {
        if (! str_contains($this->signature, '--file-names')) {
            throw new \InvalidArgumentException('--file-names option is not allowed in this command: '.self::class);
        }

        $fileNames = $this->option('file-names') ?? [];

        if (empty($fileNames) && $ask) {
            $fileNames = $this->choice(
                'Which files do you want to export?',
                TranslationHandler::getOptions()->fileNames,
                implode(', ', TranslationHandler::getOptions()->fileNames),
                null,
                true
            );
        }

        if (! is_array($fileNames)) {
            throw new \InvalidArgumentException('Invalid file names option, must be an array');
        }

        if (! empty($fileNames)) {
            $this->comment('Exporting files: '.implode(', ', $fileNames));
        }

        return $fileNames;
    }

    protected function getTranslationLocalesOption(bool $ask = false): array
    {
        if (! str_contains($this->signature, '--locales')) {
            throw new \InvalidArgumentException('--locales option is not allowed in this command: '.self::class);
        }

        $locales = $this->option('locales') ?? [];

        if (empty($locales) && $ask) {
            $locales = $this->choice(
                'Which locales do you want to export?',
                TranslationHandler::getOptions()->locales,
                implode(', ', TranslationHandler::getOptions()->locales),
                null,
                true
            );
        }

        if (! is_array($locales)) {
            throw new \InvalidArgumentException('Invalid locales option, must be an array');
        }

        if (! empty($locales)) {
            $this->comment('Exporting locales: '.implode(', ', $locales));
        }

        return $locales;
    }

    protected function getTranslationForceOption(bool $ask = false): bool
    {
        if (! str_contains($this->signature, '--force')) {
            throw new \InvalidArgumentException('--force option is not allowed in this command: '.self::class);
        }

        $force = $this->hasOption('force');

        if (! $force && $ask) {
            $force = $this->confirm(
                'Do you want to overwrite the existing translations?',
                false
            );
        }

        if (is_bool($force) && $force) {
            $this->comment('Overwriting existing translations');
        }

        return $force;
    }

    protected function getTranslationGuidedOption(): bool
    {
        if (! str_contains($this->signature, '--guided')) {
            throw new \InvalidArgumentException('--guided option is not allowed in this command: '.self::class);
        }

        return $this->hasOption('guided');
    }
}
