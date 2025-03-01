<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class ExportCommand extends Command
{
    use HasTranslationArguments;
    use HasTranslationOptions;

    public $signature = 'translation-handler:export {--guided} {--force} {--locales=*} {--from-type} {--from-path} {--from-file-names=*} {--to-type} {--to-path} {--to-file-names=*}';

    public $description = 'Export translations';

    public function handle(): int
    {
        $guided = $this->getTranslationGuidedOption('guided');

        $force = $this->getTranslationForceOption('force', $guided);

        $options = TranslationHandler::getOptions();

        $fromType = $this->getTranslationTypeOption('from-type', $options->defaultFromType, $guided);

        $toType = $this->getTranslationTypeOption('to-type', $options->defaultToType, $guided);

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
