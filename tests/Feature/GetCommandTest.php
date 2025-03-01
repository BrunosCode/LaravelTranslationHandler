<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GetCommand common', function () {
    it('asks questions if no arguments are provided', function () {
        $this->artisan('translation-handler:get')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', 'test')
            ->expectsQuestion('What is the translation locale?', 'it');
    });

    it('throws error if no from argument is provided', function () {
        $this->artisan('translation-handler:get')
            ->expectsQuestion('From where do you want to import translations?', '')
            ->expect();
    })->throws(InvalidArgumentException::class);

    it('throws error if no key argument is provided', function () {
        $this->artisan('translation-handler:get')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', '')
            ->assertFailed();
    })->throws(InvalidArgumentException::class);

    it('throws error if no locale argument is provided', function () {
        $this->artisan('translation-handler:get')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', 'test')
            ->expectsQuestion('What is the translation locale?', '')
            ->assertFailed();
    })->throws(InvalidArgumentException::class);
})->group('GetCommand');

describe('GetCommand php', function () {
    beforeEach(function () {
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('can get translation if it exists', function () {
        $this->artisan('translation-handler:get')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', 'test1.get')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsOutput('Getting translation...')
            ->expectsOutput('Translation found')
            ->expectsOutput('get-1-it')
            ->assertSuccessful();
    });

    it('cannot get translation if it does not exist', function () {
        $this->artisan('translation-handler:get')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', 'error')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsOutput('Getting translation...')
            ->expectsOutput('Translation not found!')
            ->assertFailed();
    });
})->group('GetCommand', 'PhpFileHandler');

describe('GetCommand json', function () {
    beforeEach(function () {
        $this->prepareJsonTranslations();
    });

    afterEach(function () {
        $this->cleanJsonTranslations();
    });

    it('can get translation if it exists', function () {
        $this->artisan('translation-handler:get')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::JSON)
            ->expectsQuestion('What is the translation key?', 'test1.get')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsOutput('Getting translation...')
            ->expectsOutput('Translation found')
            ->expectsOutput('get-1-it')
            ->assertSuccessful();
    });

    it('cannot get translation if it does not exist', function () {
        $this->artisan('translation-handler:get')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::JSON)
            ->expectsQuestion('What is the translation key?', 'error')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsOutput('Getting translation...')
            ->expectsOutput('Translation not found!')
            ->assertFailed();
    });
})->group('GetCommand', 'JsonFileHandler');

describe('GetCommand csv', function () {
    beforeEach(function () {
        $this->prepareCsvTranslations();
    });

    afterEach(function () {
        $this->cleanCsvTranslations();
    });

    it('can get translation if it exists', function () {
        $this->artisan('translation-handler:get')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::CSV)
            ->expectsQuestion('What is the translation key?', 'test1.get')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsOutput('Getting translation...')
            ->expectsOutput('Translation found')
            ->expectsOutput('get-1-it')
            ->assertSuccessful();
    });

    it('cannot get translation if it does not exist', function () {
        $this->artisan('translation-handler:get')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::CSV)
            ->expectsQuestion('What is the translation key?', 'error')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsOutput('Getting translation...')
            ->expectsOutput('Translation not found!')
            ->assertFailed();
    });
})->group('GetCommand', 'CsvFileHandler');

describe('GetCommand database', function () {
    beforeEach(function () {
        $this->prepareDbTranslations();
    });

    afterEach(function () {
        $this->cleanDbTranslations();
    });

    it('can get translation if it exists', function () {
        $this->artisan('translation-handler:get')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::DB)
            ->expectsQuestion('What is the translation key?', 'test1.get')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsOutput('Getting translation...')
            ->expectsOutput('Translation found')
            ->expectsOutput('get-1-it')
            ->assertSuccessful();
    });

    it('cannot get translation if it does not exist', function () {
        $this->artisan('translation-handler:get')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::DB)
            ->expectsQuestion('What is the translation key?', 'error')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsOutput('Getting translation...')
            ->expectsOutput('Translation not found!')
            ->assertFailed();
    });
})->group('GetCommand', 'DatabaseHandler');
