<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class ListCommand extends Command
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler:list
                            {from?}
                            {--from-path=}
                            {--locale=}
                            {--group=}';

    public $description = 'List translations from a storage format';

    public function handle(): int
    {
        $from = $this->getTranslationFromArgument();

        $fromPath = $this->getTranslationFromPathOption();

        $locale = $this->option('locale');

        $group = $this->option('group');

        $collection = TranslationHandler::get(from: $from, path: $fromPath);

        if ($locale) {
            $collection = $collection->whereLocale($locale);
        }

        if ($group) {
            $collection = $collection->whereGroup($group);
        }

        $this->table(
            ['Key', 'Locale', 'Value'],
            $collection->map(fn ($t) => [$t->key, $t->locale, $t->value])->all()
        );

        $this->comment('Total: '.$collection->count());

        return self::SUCCESS;
    }
}
