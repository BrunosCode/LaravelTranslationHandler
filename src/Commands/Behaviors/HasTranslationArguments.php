<?php

namespace BrunosCode\TranslationHandler\Commands\Behaviors;

use BrunosCode\TranslationHandler\Facades\TranslationHandler;

trait HasTranslationArguments
{
    protected function getTranslationTypeArgument(string $argName): string
    {
        $from = $this->argument($argName);

        if (empty($from)) {
            $from = $this->choice(
                'Which type of translations?',
                TranslationHandler::getTypes()
            );
        }

        if (empty($from) || ! in_array($from, TranslationHandler::getTypes())) {
            throw new \InvalidArgumentException('Invalid type: '.$from);
        }

        $this->comment('Type: '.$from);

        return $from;
    }

    protected function getTranslationKeyArgument(string $argName): string
    {
        $key = $this->argument($argName);

        if (empty($key)) {
            $key = $this->ask('What is the translation key?');
        }

        if (empty($key)) {
            throw new \InvalidArgumentException('Invalid key argument: '.$key);
        }

        return $key;
    }

    protected function getTranslationLocaleArgument(string $argName): string
    {
        $locale = $this->argument($argName);

        if (empty($locale)) {
            $locale = $this->ask('What is the translation locale?');
        }

        if (empty($locale) || ! in_array($locale, TranslationHandler::getOptions()->locales)) {
            throw new \InvalidArgumentException('Invalid locale argument: '.$locale);
        }

        return $locale;
    }

    protected function getTranslationValueArgument(string $argName): string
    {
        $value = $this->argument($argName);

        if (empty($value)) {
            $value = $this->ask('What is the translation value?');
        }

        if (empty($value)) {
            $confirm = $this->confirm('Are you sure you want to use a empty value?', false);

            if (! $confirm) {
                throw new \InvalidArgumentException('Invalid value argument: '.$value);
            }
        }

        return $value;
    }
}
