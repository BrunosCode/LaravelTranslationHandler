<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class ExportCommand extends Command
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler:export {--force} {--from} {--from-path} {--to} {--to-path} {--file-names=*} {--locales=*} {--guided}';

    public $description = 'Export translations';

    public function handle(): int
    {
        $guided = $this->getTranslationGuidedOption();

        $force = $this->getTranslationForceOption($guided);

        $from = $this->getTranslationFromOption($guided);

        $to = $this->getTranslationToOption($guided);

        $fromPath = $this->getTranslationFromPathOption($guided);

        $toPath = $this->getTranslationToPathOption($guided);

        $fileNames = $this->getTranslationFileNamesOption($guided);

        $locales = $this->getTranslationLocalesOption($guided);

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
