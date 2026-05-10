<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class DeleteCommand extends Command
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler:delete {from?} {key?} {--locale=} {--from-path=}';

    public $description = 'Delete a translation key from a storage format';

    public function handle(): int
    {
        $from = $this->getTranslationFromArgument();

        $key = $this->getTranslationKeyArgument();

        $locale = $this->option('locale') ?: null;

        $fromPath = $this->getTranslationFromPathOption();

        $this->comment(__('Deleting translation...'));

        $count = TranslationHandler::deleteKey($from, $key, $locale, $fromPath);

        if ($count === 0) {
            $this->error(__('Translation not found!'));

            return self::FAILURE;
        }

        $this->comment(__('Deleted '.$count.' translation(s)!'));

        return self::SUCCESS;
    }
}
