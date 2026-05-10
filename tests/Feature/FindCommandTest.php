<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('FindCommand common', function () {
    it('asks questions if no arguments are provided', function () {
        $this->artisan('translation-handler:find')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', 'test')
            ->expectsQuestion('What is the translation locale?', 'it');
    });

    it('throws error if no from argument is provided', function () {
        $this->artisan('translation-handler:find')
            ->expectsQuestion('From where do you want to import translations?', '');
    })->throws(InvalidArgumentException::class);

    it('throws error if no key argument is provided', function () {
        $this->artisan('translation-handler:find')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', '');
    })->throws(InvalidArgumentException::class);

    it('throws error if no locale argument is provided', function () {
        $this->artisan('translation-handler:find')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', 'test')
            ->expectsQuestion('What is the translation locale?', '');
    })->throws(InvalidArgumentException::class);
})->group('FindCommand');

describe('FindCommand php', function () {
    beforeEach(function () {
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('can find translation if it exists', function () {
        $this->artisan('translation-handler:find', [
            'from' => TranslationOptions::PHP,
            'key' => 'test1.get',
            'locale' => 'it',
        ])
            ->expectsOutput('Reading translations from '.TranslationOptions::PHP)
            ->assertSuccessful();
    });

    it('cannot find translation if it does not exist', function () {
        $this->artisan('translation-handler:find', [
            'from' => TranslationOptions::PHP,
            'key' => 'nonexistent',
            'locale' => 'it',
        ])
            ->expectsOutput('Translation not found!')
            ->assertFailed();
    });
})->group('FindCommand', 'PhpFileHandler');

describe('FindCommand json', function () {
    beforeEach(function () {
        $this->prepareJsonTranslations();
    });

    afterEach(function () {
        $this->cleanJsonTranslations();
    });

    it('can find translation if it exists', function () {
        $this->artisan('translation-handler:find', [
            'from' => TranslationOptions::JSON,
            'key' => 'test1.get',
            'locale' => 'en',
        ])
            ->assertSuccessful();
    });

    it('cannot find translation if it does not exist', function () {
        $this->artisan('translation-handler:find', [
            'from' => TranslationOptions::JSON,
            'key' => 'nonexistent',
            'locale' => 'en',
        ])
            ->expectsOutput('Translation not found!')
            ->assertFailed();
    });
})->group('FindCommand', 'JsonFileHandler');

describe('FindCommand csv', function () {
    beforeEach(function () {
        $this->prepareCsvTranslations();
    });

    afterEach(function () {
        $this->cleanCsvTranslations();
    });

    it('can find translation if it exists', function () {
        $this->artisan('translation-handler:find', [
            'from' => TranslationOptions::CSV,
            'key' => 'test1.get',
            'locale' => 'en',
        ])
            ->assertSuccessful();
    });

    it('cannot find translation if it does not exist', function () {
        $this->artisan('translation-handler:find', [
            'from' => TranslationOptions::CSV,
            'key' => 'nonexistent',
            'locale' => 'en',
        ])
            ->expectsOutput('Translation not found!')
            ->assertFailed();
    });
})->group('FindCommand', 'CsvFileHandler');

describe('FindCommand database', function () {
    beforeEach(function () {
        $this->prepareDbTranslations();
    });

    afterEach(function () {
        $this->cleanDbTranslations();
    });

    it('can find translation if it exists', function () {
        $this->artisan('translation-handler:find', [
            'from' => TranslationOptions::DB,
            'key' => 'test1.get',
            'locale' => 'it',
        ])
            ->assertSuccessful();
    });

    it('cannot find translation if it does not exist', function () {
        $this->artisan('translation-handler:find', [
            'from' => TranslationOptions::DB,
            'key' => 'nonexistent',
            'locale' => 'it',
        ])
            ->expectsOutput('Translation not found!')
            ->assertFailed();
    });
})->group('FindCommand', 'DatabaseHandler');
