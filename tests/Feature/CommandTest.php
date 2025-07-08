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

describe('Command common', function () {
    it('does not ask questions if guided is not used', function () {
        $options = TranslationHandler::getOptions();

        $this
            ->artisan('translation-handler', [])
            ->expectsQuestion('From where do you want to import translations?', $options->defaultExportFrom)
            ->expectsOutput('Exporting translations from '.$options->defaultExportFrom)
            ->expectsQuestion('To where do you want to export translations?', $options->defaultExportTo)
            ->expectsOutput('Exporting translations to '.$options->defaultExportTo)
            ->expectsOutput('Exporting files: '.implode(', ', $options->fileNames))
            ->expectsOutput('Exporting locales: '.implode(', ', $options->locales))
            ->expectsOutput('Starting...')
            ->expectsOutput('Finished!')
            ->assertSuccessful();
    });

    it('will force translations if force is used', function () {
        $this
            ->artisan('translation-handler', [
                'from' => TranslationOptions::PHP,
                'to' => TranslationOptions::JSON,
                '--force' => true,
            ])
            ->expectsOutput('Overwriting existing translations');
    });

    it('will delete old translations if fresh is used', function () {
        $this
            ->artisan('translation-handler', [
                'from' => TranslationOptions::PHP,
                'to' => TranslationOptions::JSON,
                '--fresh' => true,
            ])
            ->expectsOutput('Deleting existing translations before creating new ones')
            ->expectsOutput('Starting...')
            ->expectsOutput('Old translations deleted!');
    });

    it('asks questions if guided is used', function () {
        $options = TranslationHandler::getOptions();

        $this
            ->artisan('translation-handler', [
                '--guided' => true,
            ])
            ->expectsQuestion('Do you want to overwrite the existing translations?', false)
            ->expectsQuestion('Do you want to delete the existing translations before creating new ones?', false)
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsOutput('Exporting translations from '.TranslationOptions::PHP)
            ->expectsQuestion('From which path do you want to import translations?', $options->phpPath)
            ->expectsOutput('Importing translations from path '.$options->phpPath)
            ->expectsQuestion('To where do you want to export translations?', TranslationOptions::JSON)
            ->expectsOutput('Exporting translations to '.TranslationOptions::JSON)
            ->expectsQuestion('To which path do you want to export translations?', $options->jsonPath)
            ->expectsOutput('Exporting translations to path '.$options->jsonPath)
            ->expectsQuestion('Which files do you want to export?', ['test1'])
            ->expectsOutput('Exporting files: '.implode(', ', ['test1']))
            ->expectsQuestion('Which locales do you want to export?', ['it'])
            ->expectsOutput('Exporting locales: '.implode(', ', ['it']))
            ->expectsOutput('Starting...')
            ->expectsOutput('Finished!')
            ->assertSuccessful();
    });

    it('throws exception if from option does not exist in TranslationHandler::getTypes and is not empty', function () {
        $this
            ->artisan('translation-handler', [
                '--guided' => true,
            ])
            ->expectsQuestion('Do you want to overwrite the existing translations?', false)
            ->expectsQuestion('Do you want to delete the existing translations before creating new ones?', false)
            ->expectsQuestion('From where do you want to import translations?', 'error');
    })->throws(InvalidArgumentException::class);

    it('throws exception if to option does not exist in TranslationHandler::getTypes and is not empty', function () {
        $this
            ->artisan('translation-handler')
            ->expectsQuestion('From where do you want to import translations?', 'error');
    })->throws(InvalidArgumentException::class);

    it('fails if no translations are exported', function () {
        $this
            ->artisan('translation-handler', [
                'from' => TranslationOptions::PHP,
                'to' => TranslationOptions::JSON,
                '--file-names' => ['test13'],
                '--locales' => ['pt'],
            ])
            ->expectsOutput('Exporting files: '.implode(', ', ['test13']))
            ->expectsOutput('Exporting locales: '.implode(', ', ['pt']))
            ->assertFailed();
    });
})->group('Command');
