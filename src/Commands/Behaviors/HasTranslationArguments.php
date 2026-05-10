<?php

namespace BrunosCode\TranslationHandler\Commands\Behaviors;

use BrunosCode\TranslationHandler\Facades\TranslationHandler;

trait HasTranslationArguments
{
    protected function getTranslationFromArgument(): string
    {
        $from = $this->argument('from');
        $types = TranslationHandler::getTypes();

        if (empty($from)) {
            $from = $this->choice(
                'From where do you want to import translations?',
                $types
            );
        }

        if (empty($from) || ! in_array($from, $types)) {
            throw new \InvalidArgumentException("Invalid from type '{$from}'. Valid types: ".implode(', ', $types));
        }

        $this->comment('Reading translations from '.$from);

        return $from;
    }

    protected function getTranslationToArgument(): string
    {
        $to = $this->argument('to');
        $types = TranslationHandler::getTypes();

        if (empty($to)) {
            $to = $this->choice(
                'To where do you want to write translations?',
                $types
            );
        }

        if (empty($to) || ! in_array($to, $types)) {
            throw new \InvalidArgumentException("Invalid to type '{$to}'. Valid types: ".implode(', ', $types));
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

        $locales = TranslationHandler::getOptions()->locales;

        if (empty($locale) || ! in_array($locale, $locales)) {
            throw new \InvalidArgumentException("Invalid locale '{$locale}'. Configured locales: ".implode(', ', $locales));
        }

        return $locale;
    }

    protected function getTranslationGroupArgument(): string
    {
        $group = $this->argument('group');

        if (empty($group)) {
            $group = $this->ask('What is the translation group?');
        }

        if (empty($group)) {
            throw new \InvalidArgumentException('Translation group cannot be empty');
        }

        return $group;
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
