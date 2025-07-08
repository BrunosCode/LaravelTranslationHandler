<?php

namespace BrunosCode\TranslationHandler\Commands\Behaviors;

use BrunosCode\TranslationHandler\Facades\TranslationHandler;

trait HasTranslationOptions
{
    protected function getTranslationFromOption(string $default, bool $ask = false): ?string
    {
        if (! $this->hasOption('from')) {
            throw new \InvalidArgumentException('--from option is not allowed in this command: '.self::class);
        }

        $from = $this->option('from');

        if (empty($from) && $ask) {
            $from = $this->choice(
                'From where do you want to import translations?',
                TranslationHandler::getTypes(),
            );
        }

        if (empty($from)) {
            $from = $default;
        }

        if (! in_array($from, TranslationHandler::getTypes())) {
            throw new \InvalidArgumentException('Invalid from argument type: '.$from);
        }

        $this->comment('Exporting translations from '.$from);

        return $from;
    }

    protected function getTranslationToOption(string $default, bool $ask = false): ?string
    {
        if (! $this->hasOption('to')) {
            throw new \InvalidArgumentException('--to option is not allowed in this command: '.self::class);
        }

        $to = $this->option('to');

        if (empty($to) && $ask) {
            $to = $this->choice(
                'To where do you want to export translations?',
                TranslationHandler::getTypes(),
            );
        }

        if (empty($to)) {
            $to = $default;
        }

        if (! in_array($to, TranslationHandler::getTypes())) {
            throw new \InvalidArgumentException('Invalid to argument type: '.$to);
        }

        $this->comment('Exporting translations to '.$to);

        return $to;
    }

    protected function getTranslationFromPathOption(bool $ask = false): ?string
    {
        if (! $this->hasOption('from-path')) {
            throw new \InvalidArgumentException('--from-path option is not allowed in this command: '.self::class);
        }

        $fromPath = $this->option('from-path');

        if (empty($fromPath) && $ask) {
            $fromPath = $this->ask(
                'From which path do you want to import translations?',
            );
        }

        if (! empty($fromPath)) {
            $this->comment('Importing translations from path '.$fromPath);
        }

        return $fromPath ?: null;
    }

    protected function getTranslationToPathOption(bool $ask = false): ?string
    {
        if (! $this->hasOption('to-path')) {
            throw new \InvalidArgumentException('--to-path option is not allowed in this command: '.self::class);
        }

        $toPath = $this->option('to-path');

        if (empty($toPath) && $ask) {
            $toPath = $this->ask(
                'To which path do you want to export translations?',
            );
        }

        if (! empty($toPath)) {
            $this->comment('Exporting translations to path '.$toPath);
        }

        return $toPath ?: null;
    }

    protected function getTranslationFileNamesOption(array $default, bool $ask = false): array
    {
        if (! $this->hasOption('file-names')) {
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

        if (empty($fileNames)) {
            $fileNames = $default;
        }
        $this->comment('Exporting files: '.implode(', ', $fileNames));

        return $fileNames;
    }

    protected function getTranslationLocalesOption(array $default, bool $ask = false): array
    {
        if (! $this->hasOption('locales')) {
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

        if (empty($locales)) {
            $locales = $default;
        }
        $this->comment('Exporting locales: '.implode(', ', $locales));

        return $locales;
    }

    protected function getTranslationForceOption(bool $ask = false): bool
    {
        if (! $this->hasOption('force')) {
            throw new \InvalidArgumentException('--force option is not allowed in this command: '.self::class);
        }

        $force = $this->option('force') ?? false;

        if (! $force && $ask) {
            $force = $this->confirm(
                'Do you want to overwrite the existing translations?',
                false
            );
        }

        if (! is_bool($force)) {
            throw new \InvalidArgumentException('Invalid force option, must be a boolean');
        }

        if ($force) {
            $this->comment('Overwriting existing translations');
        }

        return $force;
    }

    protected function getTranslationFreshOption(bool $ask = false): bool
    {
        if (! $this->hasOption('fresh')) {
            throw new \InvalidArgumentException('--fresh option is not allowed in this command: '.self::class);
        }

        $fresh = $this->option('fresh') ?? false;

        if (! $fresh && $ask) {
            $fresh = $this->confirm(
                'Do you want to delete the existing translations before creating new ones?',
                false
            );
        }

        if (! is_bool($fresh)) {
            throw new \InvalidArgumentException('Invalid fresh option, must be a boolean');
        }

        if ($fresh) {
            $this->comment('Deleting existing translations before creating new ones');
        }

        return $fresh;
    }

    protected function getTranslationGuidedOption(): bool
    {
        if (! $this->hasOption('guided')) {
            throw new \InvalidArgumentException('--guided option is not allowed in this command: '.self::class);
        }

        return $this->option('guided');
    }
}
