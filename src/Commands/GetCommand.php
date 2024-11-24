<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class GetCommand extends Command
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler:get {from?} {key?} {locale?} {--from-path=}';

    public $description = 'Export translations';

    public function handle(): int
    {
        $from = $this->getTranslationFromArgument();

        $key = $this->getTranslationKeyArgument();

        $locale = $this->getTranslationLocaleArgument();

        $fromPath = $this->getTranslationFromPathOption();

        $this->comment(__('Getting translation...'));

        $translation = TranslationHandler::get(from: $from, path: $fromPath)
            ->whereKey($key)
            ->whereLocale($locale)
            ->first();

        if (! $translation) {
            $this->error(__('Translation not found!'));

            return self::FAILURE;
        }
        $this->option('from-path');
        $this->comment(__('Translation found'));

        $this->info($translation->value);

        return self::SUCCESS;
    }
}
