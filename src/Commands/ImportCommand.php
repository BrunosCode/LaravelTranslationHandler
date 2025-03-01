<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class ImportCommand extends Command
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler:import {--force} {--from} {--from-path} {--to} {--to-path} {--file-names=*} {--locales=*} {--guided}';

    public $description = 'Import translations';

    public function handle(): int
    {
        $guided = $this->getTranslationGuidedOption();

        $force = $this->getTranslationForceOption($guided);

        $options = TranslationHandler::getOptions();

        $from = $this->getTranslationFromOption($options->defaultImportFrom, $guided);

        $to = $this->getTranslationToOption($options->defaultImportTo, $guided);

        $fromPath = $this->getTranslationFromPathOption($guided);

        $toPath = $this->getTranslationToPathOption($guided);

        $fileNames = $this->getTranslationFileNamesOption($options->fileNames, $guided);

        $locales = $this->getTranslationLocalesOption($options->locales, $guided);

        $this->comment(__('Starting Import...'));

        $success = TranslationHandler::resetOptions()
            ->setOption('fileNames', $fileNames)
            ->setOption('locales', $locales)
            ->import(
                from: $from,
                to: $to,
                force: $force,
                fromPath: $fromPath,
                toPath: $toPath,
            );

        if (! $success) {
            $this->error(__('Import failed!'));

            return self::FAILURE;
        }

        $this->comment(__('Import successful!'));

        return self::SUCCESS;
    }
}
