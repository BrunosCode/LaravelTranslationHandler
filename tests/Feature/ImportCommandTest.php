<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->preparePhpTranslations();
    $this->prepareJsonTranslations();
});

afterEach(function () {
    $this->cleanPhpTranslations();
    $this->cleanJsonTranslations();
});

describe('ImportCommand common', function () {
    it('does not ask questions if guided is not used', function () {
        $options = TranslationHandler::getOptions();

        $this->artisan('translation-handler:import')
            ->expectsOutput('Reading translations from '.$options->defaultImportFrom)
            ->expectsOutput('Writing translations to '.$options->defaultImportTo)
            ->expectsOutput('Files: '.implode(', ', $options->fileNames))
            ->expectsOutput('Locales: '.implode(', ', $options->locales))
            ->expectsOutput('Starting Import...')
            ->expectsOutput('Import successful!')
            ->assertSuccessful();
    });

    it('will force translations if force is used', function () {
        $this
            ->artisan('translation-handler:import', [
                '--force' => true,
            ])
            ->expectsOutput('Overwriting existing translations');
    });

    it('will delete old translations if fresh is used', function () {
        $options = TranslationHandler::getOptions();

        $this
            ->artisan('translation-handler:export', [
                '--fresh' => true,
            ])
            ->expectsOutput('Deleting existing translations before creating new ones')
            ->expectsOutput('Reading translations from '.$options->defaultExportFrom)
            ->expectsOutput('Writing translations to '.$options->defaultExportTo)
            ->expectsOutput('Files: '.implode(', ', $options->fileNames))
            ->expectsOutput('Locales: '.implode(', ', $options->locales))
            ->expectsOutput('Starting Export...')
            ->expectsOutput('Old translations deleted!');
    });

    it('asks questions if guided is used', function () {
        $options = TranslationHandler::getOptions();

        $this
            ->artisan('translation-handler:import', [
                '--guided' => true,
            ])
            ->expectsQuestion('Do you want to overwrite the existing translations?', false)
            ->expectsQuestion('Do you want to delete the existing translations before creating new ones?', false)
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsOutput('Reading translations from '.TranslationOptions::PHP)
            ->expectsQuestion('To where do you want to write translations?', TranslationOptions::JSON)
            ->expectsOutput('Writing translations to '.TranslationOptions::JSON)
            ->expectsQuestion('From which path do you want to import translations?', $options->phpPath)
            ->expectsOutput('Reading translations from path '.$options->phpPath)
            ->expectsQuestion('To which path do you want to export translations?', $options->jsonPath)
            ->expectsOutput('Writing translations to path '.$options->jsonPath)
            ->expectsQuestion('Which files do you want to export?', ['test1'])
            ->expectsOutput('Files: '.implode(', ', ['test1']))
            ->expectsQuestion('Which locales do you want to export?', ['it'])
            ->expectsOutput('Locales: '.implode(', ', ['it']))
            ->expectsOutput('Starting Import...')
            ->expectsOutput('Import successful!')
            ->assertSuccessful();
    });

    it('throws exception if from option does not exist in TranslationHandler::getTypes and is not empty', function () {
        $this
            ->artisan('translation-handler:import', [
                '--guided' => true,
            ])
            ->expectsQuestion('Do you want to overwrite the existing translations?', false)
            ->expectsQuestion('Do you want to delete the existing translations before creating new ones?', false)
            ->expectsQuestion('From where do you want to import translations?', 'error');
    })->throws(InvalidArgumentException::class);

    it('throws exception if to option does not exist in TranslationHandler::getTypes and is not empty', function () {
        $this
            ->artisan('translation-handler:import', [
                '--guided' => true,
            ])
            ->expectsQuestion('Do you want to overwrite the existing translations?', false)
            ->expectsQuestion('Do you want to delete the existing translations before creating new ones?', false)
            ->expectsQuestion('From where do you want to import translations?', '')
            ->expectsQuestion('To where do you want to write translations?', 'error');
    })->throws(InvalidArgumentException::class);

    it('fails if no translations are imported', function () {
        $this
            ->artisan('translation-handler:import', [
                '--file-names' => ['test13'],
                '--locales' => ['pt'],
            ])
            ->expectsOutput('Files: '.implode(', ', ['test13']))
            ->expectsOutput('Locales: '.implode(', ', ['pt']))
            ->expectsOutput('Starting Import...')
            ->expectsOutput('Import failed!')
            ->assertFailed();
    });
})->group('ImportCommand');
