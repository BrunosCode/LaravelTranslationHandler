<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class ExportCommand extends Command
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler:export {--force} {--from} {--from-path} {--file-names=*} {--locales=*} {--to} {--to-path} {--guided}';

    public $description = 'Export translations';

    public function handle(): int
    {
        $guided = $this->getTranslationGuidedOption();

        $force = $this->getTranslationForceOption($guided);

        $options = TranslationHandler::getOptions();

        $from = $this->getTranslationFromOption($options->defaultExportFrom, $guided);

        $to = $this->getTranslationToOption($options->defaultExportTo, $guided);

        $fromPath = $this->getTranslationFromPathOption($guided);

        $toPath = $this->getTranslationToPathOption($guided);

        $fileNames = $this->getTranslationFileNamesOption($options->fileNames, $guided);

        $locales = $this->getTranslationLocalesOption($options->locales, $guided);

        $this->comment(__('Starting Export...'));

        $success = TranslationHandler::resetOptions()
            ->setOption('fileNames', $fileNames)
            ->setOption('locales', $locales)
            ->export(
                from: $from,
                to: $to,
                force: $force,
                fromPath: $fromPath,
                toPath: $toPath,
            );

        if (! $success) {
            $this->error(__('Export failed!'));

            return self::FAILURE;
        }

        $this->comment(__('Export finished!'));

        return self::SUCCESS;
    }
}
