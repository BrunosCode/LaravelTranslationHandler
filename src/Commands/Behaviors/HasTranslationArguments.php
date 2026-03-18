<?php

namespace BrunosCode\TranslationHandler\Commands\Behaviors;

use BrunosCode\TranslationHandler\Facades\TranslationHandler;

trait HasTranslationArguments
{
    protected function getTranslationFromArgument(): string
    {
        $from = $this->argument('from');

        if (empty($from)) {
            $from = $this->choice(
                'From where do you want to import translations?',
                TranslationHandler::getTypes()
            );
        }

        if (empty($from) || ! in_array($from, TranslationHandler::getTypes())) {
            throw new \InvalidArgumentException("Invalid from type '{$from}'. Valid types: ".implode(', ', TranslationHandler::getTypes()));
        }

        $this->comment('Reading translations from '.$from);

        return $from;
    }

    protected function getTranslationToArgument(): string
    {
        $to = $this->argument('to');

        if (empty($to)) {
            $to = $this->choice(
                'To where do you want to write translations?',
                TranslationHandler::getTypes()
            );
        }

        if (empty($to) || ! in_array($to, TranslationHandler::getTypes())) {
            throw new \InvalidArgumentException("Invalid to type '{$to}'. Valid types: ".implode(', ', TranslationHandler::getTypes()));
        }

        $this->comment('Writing translations to '.$to);

        return $to;
    }

    protected function getTranslationKeyArgument(): string
    {
        $key = $this->argument('key');

        if (empty($key)) {
            $key = $this->ask('What is the translation key?');
        }

        if (empty($key)) {
            throw new \InvalidArgumentException('Translation key cannot be empty');
        }

        return $key;
    }

    protected function getTranslationLocaleArgument(): string
    {
        $locale = $this->argument('locale');

        if (empty($locale)) {
            $locale = $this->ask('What is the translation locale?');
        }

        if (empty($locale) || ! in_array($locale, TranslationHandler::getOptions()->locales)) {
            throw new \InvalidArgumentException("Invalid locale '{$locale}'. Configured locales: ".implode(', ', TranslationHandler::getOptions()->locales));
        }

        return $locale;
    }

    protected function getTranslationValueArgument(): string
    {
        $value = $this->argument('value');

        if (empty($value)) {
            $value = $this->ask('What is the translation value?');
        }

        if (empty($value)) {
            $confirm = $this->confirm('Are you sure you want to use a empty value?', false);

            if (! $confirm) {
                throw new \InvalidArgumentException('Translation value cannot be empty');
            }
        }

        return $value;
    }
}
