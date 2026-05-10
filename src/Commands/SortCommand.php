<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class SortCommand extends Command
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler:sort {from?} {--from-path=} {--locale=*} {--group=*}';

    public $description = 'Sort translation keys alphabetically in a storage format (php, json, csv)';

    public function handle(): int
    {
        $from = $this->getTranslationFromArgument(TranslationOptions::SORTABLE_TYPES);

        $fromPath = $this->getTranslationFromPathOption();

        $locales = $this->option('locale') ?: [];

        $groups = $this->option('group') ?: [];

        $this->comment(__('Sorting translations...'));

        $count = TranslationHandler::sortKeys($from, $locales, $groups, $fromPath);

        if ($count === 0) {
            $this->error(__('No translations found to sort!'));

            return self::FAILURE;
        }

        $this->comment(__('Sorted '.$count.' translation(s)!'));

        return self::SUCCESS;
    }
}
