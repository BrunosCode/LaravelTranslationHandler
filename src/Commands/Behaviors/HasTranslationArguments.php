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
            throw new \InvalidArgumentException('Invalid from argument type: '.$from);
        }

        $this->comment('Importing translations from '.$from);

        return $from;
    }

    protected function getTranslationToArgument(): string
    {
        $to = $this->argument('to');

        if (empty($to)) {
            $to = $this->choice(
                'To where do you want to export translations?',
                TranslationHandler::getTypes()
            );
        }

        if (empty($to) || ! in_array($to, TranslationHandler::getTypes())) {
            throw new \InvalidArgumentException('Invalid to argument type: '.$to);
        }

        $this->comment('Exporting translations to '.$to);

        return $to;
    }

    protected function getTranslationKeyArgument(): string
    {
        $key = $this->argument('key');

        if (empty($key)) {
            $key = $this->ask('What is the translation key?');
        }

        if (empty($key)) {
            throw new \InvalidArgumentException('Invalid key argument: '.$key);
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
            throw new \InvalidArgumentException('Invalid locale argument: '.$locale);
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
                throw new \InvalidArgumentException('Invalid value argument: '.$value);
            }
        }

        return $value;
    }
}
