<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class TranslationHandlerImportCommand extends Command
{
    public $signature = 'translation-handler:import {--force} {--defaults} {--from=} {--to=} {--file-names=*} {--locales=*}';

    public $description = 'Import translations';

    public function handle(): int
    {
        $defaults = $this->option('defaults');
        if ($defaults) {
            $this->comment('Importing using the default values');
        }
        
        $from = $this->option('from');
        if (!$defaults && empty($from)) {
            $from = $this->choice('From where do you want to import?', TranslationHandler::getTypes(), TranslationHandler::getDefaultExportFrom());
        }
        if (!empty($from)) {
            $this->comment('Importing from ' . $from);
        }

        $to = $this->option('to');
        if (!$defaults && empty($to)) {
            $to = $this->choice('To where do you want to import?', TranslationHandler::getTypes(), TranslationHandler::getDefaultExportTo());
        }
        if (!empty($to)) {
            $this->comment('Importing to ' . $to);
        }

        $fileNames = $this->option('file-names');
        if (!$defaults && empty($fileNames)) {
            $fileNames = $this->choice('Which files do you want to import?', TranslationHandler::getFileNames(), null, null, true);
        } else {
            $fileNames = TranslationHandler::getFileNames();
        }
        if (!empty($fileNames)) {
            $this->comment('Importing files: ' . implode(', ', $fileNames));
        }

        $locales = $this->option('locales');
        if (!$defaults && empty($locales)) {
            $locales = $this->choice('Which locales do you want to import?', TranslationHandler::getLocales(), null, null, true);
        } else {
            $locales = TranslationHandler::getLocales();
        }
        if (!empty($locales)) {
            $this->comment('Importing locales: ' . implode(', ', $locales));
        }

        $force = $this->option('force');
        $confirmForce = false;
        if ($force) {
            $confirmForce = $this->confirm('Are you sure you want to overwrite the existing translations?', false);
        }

        $this->comment('Starting import...');

        if (!TranslationHandler::import($from, $to, $fileNames, $locales, $confirmForce)) {
            $this->error('Export failed!');
            return self::FAILURE;
        }

        $this->comment('Export finished!');
        return self::SUCCESS;
    }
}
