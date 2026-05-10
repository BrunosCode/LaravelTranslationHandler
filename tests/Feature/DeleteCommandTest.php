<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DeleteCommand common', function () {
    it('asks questions if no arguments are provided', function () {
        $this->artisan('translation-handler:delete')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', 'test');
    });

    it('throws error if no from argument is provided', function () {
        $this->artisan('translation-handler:delete')
            ->expectsQuestion('From where do you want to import translations?', '');
    })->throws(InvalidArgumentException::class);

    it('throws error if no key argument is provided', function () {
        $this->artisan('translation-handler:delete')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', '');
    })->throws(InvalidArgumentException::class);
})->group('DeleteCommand');

describe('DeleteCommand php', function () {
    beforeEach(function () {
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('deletes a key for all locales', function () {
        $this->artisan('translation-handler:delete', [
            'from' => TranslationOptions::PHP,
            'key' => 'test1.get',
        ])
            ->assertSuccessful();

        expect(TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.get'))->toHaveCount(0);
    });

    it('deletes a key for a specific locale', function () {
        $this->artisan('translation-handler:delete', [
            'from' => TranslationOptions::PHP,
            'key' => 'test1.get',
            '--locale' => 'en',
        ])
            ->assertSuccessful();

        expect(TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.get')->whereLocale('en'))->toHaveCount(0);
        expect(TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.get')->whereLocale('it'))->not->toHaveCount(0);
    });

    it('fails when translation does not exist', function () {
        $this->artisan('translation-handler:delete', [
            'from' => TranslationOptions::PHP,
            'key' => 'nonexistent.key',
        ])
            ->expectsOutput('Translation not found!')
            ->assertFailed();
    });
})->group('DeleteCommand', 'PhpFileHandler');

describe('DeleteCommand json', function () {
    beforeEach(function () {
        $this->prepareJsonTranslations();
    });

    afterEach(function () {
        $this->cleanJsonTranslations();
    });

    it('deletes a key for all locales', function () {
        $this->artisan('translation-handler:delete', [
            'from' => TranslationOptions::JSON,
            'key' => 'test1.get',
        ])
            ->assertSuccessful();

        expect(TranslationHandler::get(TranslationOptions::JSON)->whereKey('test1.get'))->toHaveCount(0);
    });

    it('fails when translation does not exist', function () {
        $this->artisan('translation-handler:delete', [
            'from' => TranslationOptions::JSON,
            'key' => 'nonexistent.key',
        ])
            ->expectsOutput('Translation not found!')
            ->assertFailed();
    });
})->group('DeleteCommand', 'JsonFileHandler');

describe('DeleteCommand csv', function () {
    beforeEach(function () {
        $this->prepareCsvTranslations();
    });

    afterEach(function () {
        $this->cleanCsvTranslations();
    });

    it('deletes a key for all locales', function () {
        $this->artisan('translation-handler:delete', [
            'from' => TranslationOptions::CSV,
            'key' => 'test1.get',
        ])
            ->assertSuccessful();

        expect(TranslationHandler::get(TranslationOptions::CSV)->whereKey('test1.get'))->toHaveCount(0);
    });

    it('fails when translation does not exist', function () {
        $this->artisan('translation-handler:delete', [
            'from' => TranslationOptions::CSV,
            'key' => 'nonexistent.key',
        ])
            ->expectsOutput('Translation not found!')
            ->assertFailed();
    });
})->group('DeleteCommand', 'CsvFileHandler');

describe('DeleteCommand database', function () {
    beforeEach(function () {
        $this->prepareDbTranslations();
    });

    afterEach(function () {
        $this->cleanDbTranslations();
    });

    it('deletes a key for all locales', function () {
        $this->artisan('translation-handler:delete', [
            'from' => TranslationOptions::DB,
            'key' => 'test1.get',
        ])
            ->assertSuccessful();

        expect(TranslationHandler::get(TranslationOptions::DB)->whereKey('test1.get'))->toHaveCount(0);
    });

    it('deletes a key for a specific locale', function () {
        $this->artisan('translation-handler:delete', [
            'from' => TranslationOptions::DB,
            'key' => 'test1.get',
            '--locale' => 'en',
        ])
            ->assertSuccessful();

        expect(TranslationHandler::get(TranslationOptions::DB)->whereKey('test1.get')->whereLocale('en'))->toHaveCount(0);
        expect(TranslationHandler::get(TranslationOptions::DB)->whereKey('test1.get')->whereLocale('it'))->not->toHaveCount(0);
    });

    it('fails when translation does not exist', function () {
        $this->artisan('translation-handler:delete', [
            'from' => TranslationOptions::DB,
            'key' => 'nonexistent.key',
        ])
            ->expectsOutput('Translation not found!')
            ->assertFailed();
    });
})->group('DeleteCommand', 'DatabaseHandler');
