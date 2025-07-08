<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command as BaseCommand;

class Command extends BaseCommand
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler 
                            {from?} 
                            {to?} 
                            {--fresh} 
                            {--force} 
                            {--file-names=*} 
                            {--locales=*} 
                            {--from-path} 
                            {--to-path} 
                            {--guided}';

    public $description = 'Handle translations';

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

        $this->comment(__('Starting...'));

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

        $success = TranslationHandler::export(
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
