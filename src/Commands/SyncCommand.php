<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class SyncCommand extends Command
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler:sync
                            {from?}
                            {to?}
                            {--fresh}
                            {--force}
                            {--file-names=*}
                            {--locales=*}
                            {--from-path=}
                            {--to-path=}
                            {--guided}';

    public $description = 'Sync translations between formats';

    public function handle(): int
    {
        $guided = $this->getTranslationGuidedOption();

        $force = $this->getTranslationForceOption($guided);

        $fresh = $this->getTranslationFreshOption($guided);

        $from = $this->getTranslationFromArgument();

        $fromPath = $this->getTranslationFromPathOption($guided);

        $to = $this->getTranslationToArgument();

        $toPath = $this->getTranslationToPathOption($guided);

        $options = TranslationHandler::getOptions();

        $fileNames = $this->getTranslationFileNamesOption($options->fileNames, $guided);

        $locales = $this->getTranslationLocalesOption($options->locales, $guided);

        $this->comment(__('Starting sync...'));

        TranslationHandler::resetOptions()
            ->setOption('fileNames', $fileNames)
            ->setOption('locales', $locales);

        if ($fresh) {
            $int = TranslationHandler::delete(
                from: $to,
                path: $toPath,
            );

            if ($int > 0) {
                $this->comment(__('Old translations deleted!'));
            }
        }

        $success = TranslationHandler::sync(
            from: $from,
            to: $to,
            force: $force,
            fromPath: $fromPath,
            toPath: $toPath
        );

        if (! $success) {
            $this->error(__('Sync failed!'));

            return self::FAILURE;
        }

        $this->comment(__('Sync finished!'));

        return self::SUCCESS;
    }
}
