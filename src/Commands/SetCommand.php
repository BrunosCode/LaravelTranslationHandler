<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class SetCommand extends Command
{
    use HasTranslationArguments;
    use HasTranslationOptions;

    public $signature = 'translation-handler:set {to?} {key?} {locale?} {value?} {--to-path=} {--force}';

    public $description = 'Import translations';

    public function handle(): int
    {
        $to = $this->getTranslationToArgument();

        $key = $this->getTranslationKeyArgument();

        $locale = $this->getTranslationLocaleArgument();

        $value = $this->getTranslationValueArgument();

        $toPath = $this->getTranslationToPathOption();

        $force = $this->getTranslationForceOption();

        $translation = new Translation(
            key: $key,
            locale: $locale,
            value: $value
        );

        $collection = new TranslationCollection([$translation]);

        $this->comment(__('Setting translation...'));

        $count = TranslationHandler::set(
            translations: $collection,
            to: $to,
            path: $toPath,
            force: $force
        );

        if ($count === 0) {
            $this->error(__('Translation not set!'));

            return self::FAILURE;
        }

        $this->comment(__('Translation set!'));

        return self::SUCCESS;
    }
}
