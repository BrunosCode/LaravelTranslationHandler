<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command as BaseCommand;

class Command extends BaseCommand
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler {from?} {to?} {--force} {--file-names=*} {--locales=*} {--from-path} {--to-path} {--guided}';

    public $description = 'Handle translations';

    public function handle(): int
    {
        $guided = $this->getTranslationGuidedOption();

        $force = $this->getTranslationForceOption($guided);

        $from = $this->getTranslationFromArgument($guided);

        $fromPath = $this->getTranslationFromPathOption($guided);

        $to = $this->getTranslationToArgument($guided);

        $toPath = $this->getTranslationToPathOption($guided);

        $options = TranslationHandler::getOptions();

        $fileNames = $this->getTranslationFileNamesOption($options->fileNames, $guided);

        $locales = $this->getTranslationLocalesOption($options->locales, $guided);

        $this->comment(__('Starting...'));

        $success = TranslationHandler::resetOptions()
            ->setOption('fileNames', $fileNames)
            ->setOption('locales', $locales)
            ->export(
                from: $from,
                to: $to,
                force: $force,
                fromPath: $fromPath,
                toPath: $toPath
            );

        if (! $success) {
            $this->error(__('Failed!'));

            return self::FAILURE;
        }

        $this->comment(__('Finished!'));

        return self::SUCCESS;
    }
}
