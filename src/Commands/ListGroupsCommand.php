<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class ListGroupsCommand extends Command
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler:list-groups
                            {from?}
                            {--from-path=}
                            {--level=0}
                            {--search=}';

    public $description = 'List unique translation key groups from a storage format';

    public function handle(): int
    {
        $from = $this->getTranslationFromArgument();

        $fromPath = $this->getTranslationFromPathOption();

        $level = (int) ($this->option('level') ?? 0);

        $search = $this->option('search');

        $groups = TranslationHandler::listGroups($from, $fromPath, $level, $search);

        foreach ($groups as $group) {
            $this->line($group);
        }

        $this->comment('Total: '.$groups->count());

        return self::SUCCESS;
    }
}
