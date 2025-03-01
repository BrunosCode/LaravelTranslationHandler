<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command as BaseCommand;

class Command extends BaseCommand
{
    use HasTranslationArguments;
    use HasTranslationOptions;

    public $signature = 'translation-handler {from-type?} {to-type?} {--guided} {--force} {--locales=*} {--from-file-names=*} {--from-path} {--to-file-names=*} {--to-path}';

    public $description = 'Handle translations';

    public function handle(): int
    {
        $guided = $this->getTranslationGuidedOption('guided');

        $locales = $this->getTranslationLocalesOption('locales', $guided);

        $this->comment(__('Move translations from:' ));

        $fromType = $this->getTranslationTypeArgument('from-type');

        $fromPath = $this->getTranslationPathOption('from-path', $fromType, $guided);

        $fromFileNames = $this->getTranslationFileNamesOption('from-file-names', $guided);

        $this->comment(__('Move translations to:'));

        $toType = $this->getTranslationTypeOption('to', TranslationOptions::PHP, $guided);

        $toPath = $this->getTranslationPathOption('to-path', $toType, $guided);

        $toFileNames = $this->getTranslationFileNamesOption('to-file-names', $toType, $guided);

        $locales = $this->getTranslationLocalesOption('locales', $guided);

        $force = $this->getTranslationForceOption('force', $guided);

        $this->comment(__('Starting...'));

        $success = TranslationHandler::resetOptions()
            ->setOption('locales', $locales)
            ->move(
                fromType: $fromType,
                fromPath: $fromPath,
                fromFileNames: $fromFileNames,
                toType: $toType,
                toPath: $toPath,
                toFileNames: $toFileNames,
                force: $force,
            );

        if (! $success) {
            $this->error(__('Failed!'));

            return self::FAILURE;
        }

        $this->comment(__('Success!'));

        return self::SUCCESS;
    }
}
