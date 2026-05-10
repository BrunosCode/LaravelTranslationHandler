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

        $collection = TranslationHandler::get(from: $from, path: $fromPath);

        $delimiter = TranslationHandler::getOption('keyDelimiter') ?? '.';
        $depth = $level + 1;

        $groups = $collection
            ->map(fn ($t) => $t->key)
            ->unique()
            ->map(function ($key) use ($delimiter, $depth) {
                $segments = explode($delimiter, $key);

                if (count($segments) <= $depth) {
                    return null;
                }

                return implode($delimiter, array_slice($segments, 0, $depth));
            })
            ->filter()
            ->unique()
            ->when($search, fn ($items) => $items->filter(
                fn ($group) => str_contains(strtolower($group), strtolower($search))
            ))
            ->sort()
            ->values();

        foreach ($groups as $group) {
            $this->line($group);
        }

        $this->comment('Total: '.$groups->count());

        return self::SUCCESS;
    }
}
