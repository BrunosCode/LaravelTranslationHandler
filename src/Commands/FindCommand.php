<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class FindCommand extends Command
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler:find {from?} {key?} {locale?} {--from-path=}';

    public $description = 'Find a specific translation by key and locale';

    public function handle(): int
    {
        $from = $this->getTranslationFromArgument();

        $key = $this->getTranslationKeyArgument();

        $locale = $this->getTranslationLocaleArgument();

        $fromPath = $this->getTranslationFromPathOption();

        $translation = TranslationHandler::find($from, $key, $locale, $fromPath);

        if (! $translation) {
            $this->error(__('Translation not found!'));

            return self::FAILURE;
        }

        $this->table(
            ['Format', 'Key', 'Locale', 'Value'],
            [[$from, $translation->key, $translation->locale, $translation->value]]
        );

        return self::SUCCESS;
    }
}
