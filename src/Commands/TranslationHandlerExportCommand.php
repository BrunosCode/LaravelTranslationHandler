<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class TranslationHandlerExportCommand extends Command
{
    public $signature = 'translation-handler:export {--force} {--defaults} {--from=} {--to=} {--file-names=*} {--locales=*}';

    public $description = 'Export translations';

    public function handle(): int
    {
        $defaults = $this->option('defaults');
        if ($defaults) {
            $this->comment('Exporting using the default values');
        }

        $from = $this->option('from');
        if (! $defaults && empty($from)) {
            $from = $this->choice('From where do you want to export?', TranslationHandler::getTypes(), TranslationHandler::getDefaultExportFrom());
        }
        if (! empty($from)) {
            $this->comment('Exporting from '.$from);
        }

        $to = $this->option('to');
        if (! $defaults && empty($to)) {
            $to = $this->choice('To where do you want to export?', TranslationHandler::getTypes(), TranslationHandler::getDefaultExportTo());
        }
        if (! empty($to)) {
            $this->comment('Exporting to '.$to);
        }

        $fileNames = $this->option('file-names');
        if (! $defaults && empty($fileNames)) {
            $fileNames = $this->choice('Which files do you want to export?', TranslationHandler::getFileNames(), null, null, true);
        } else {
            $fileNames = TranslationHandler::getFileNames();
        }
        if (! empty($fileNames)) {
            $this->comment('Exporting files: '.implode(', ', $fileNames));
        }

        $locales = $this->option('locales');
        if (! $defaults && empty($locales)) {
            $locales = $this->choice('Which locales do you want to export?', TranslationHandler::getLocales(), null, null, true);
        } else {
            $locales = TranslationHandler::getLocales();
        }
        if (! empty($locales)) {
            $this->comment('Exporting locales: '.implode(', ', $locales));
        }

        $force = $this->option('force');
        $confirmForce = false;
        if ($force) {
            $confirmForce = $this->confirm('Are you sure you want to overwrite the existing translations?', false);
        }

        $this->comment('Starting export...');

        if (! TranslationHandler::export($from, $to, $fileNames, $locales, $confirmForce)) {
            $this->error('Export failed!');

            return self::FAILURE;
        }

        $this->comment('Export finished!');

        return self::SUCCESS;
    }
}
