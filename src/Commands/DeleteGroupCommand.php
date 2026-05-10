<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class DeleteGroupCommand extends Command
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler:delete-group {from?} {group?} {--from-path=}';

    public $description = 'Delete all translations in a key group from a storage format';

    public function handle(): int
    {
        $from = $this->getTranslationFromArgument();

        $group = $this->getTranslationGroupArgument();

        $fromPath = $this->getTranslationFromPathOption();

        $this->comment(__('Deleting group...'));

        $count = TranslationHandler::deleteGroup($from, $group, $fromPath);

        if ($count === 0) {
            $this->error(__('No translations found for group!'));

            return self::FAILURE;
        }

        $this->comment(__('Deleted '.$count.' translation(s)!'));

        return self::SUCCESS;
    }
}
