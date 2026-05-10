<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ListCommand common', function () {
    it('asks questions if no arguments are provided', function () {
        $this->artisan('translation-handler:list')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP);
    });

    it('throws error if no from argument is provided', function () {
        $this->artisan('translation-handler:list')
            ->expectsQuestion('From where do you want to import translations?', '');
    })->throws(InvalidArgumentException::class);
})->group('ListCommand');

describe('ListCommand php', function () {
    beforeEach(function () {
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('lists all translations', function () {
        $this->artisan('translation-handler:list', ['from' => TranslationOptions::PHP])
            ->expectsOutput('Total: 8')
            ->assertSuccessful();
    });

    it('filters by locale', function () {
        $this->artisan('translation-handler:list', [
            'from' => TranslationOptions::PHP,
            '--locale' => 'en',
        ])
            ->expectsOutput('Total: 4')
            ->assertSuccessful();
    });

    it('filters by group', function () {
        $this->artisan('translation-handler:list', [
            'from' => TranslationOptions::PHP,
            '--group' => 'test1',
        ])
            ->expectsOutput('Total: 4')
            ->assertSuccessful();
    });

    it('returns zero results for non-existent group', function () {
        $this->artisan('translation-handler:list', [
            'from' => TranslationOptions::PHP,
            '--group' => 'nonexistent',
        ])
            ->expectsOutput('Total: 0')
            ->assertSuccessful();
    });
})->group('ListCommand', 'PhpFileHandler');

describe('ListCommand csv', function () {
    beforeEach(function () {
        $this->prepareCsvTranslations();
    });

    afterEach(function () {
        $this->cleanCsvTranslations();
    });

    it('lists all translations', function () {
        $this->artisan('translation-handler:list', ['from' => TranslationOptions::CSV])
            ->expectsOutput('Total: 4')
            ->assertSuccessful();
    });

    it('filters by locale', function () {
        $this->artisan('translation-handler:list', [
            'from' => TranslationOptions::CSV,
            '--locale' => 'en',
        ])
            ->expectsOutput('Total: 2')
            ->assertSuccessful();
    });
})->group('ListCommand', 'CsvFileHandler');

describe('ListCommand database', function () {
    beforeEach(function () {
        $this->prepareDbTranslations();
    });

    afterEach(function () {
        $this->cleanDbTranslations();
    });

    it('lists all translations', function () {
        $this->artisan('translation-handler:list', ['from' => TranslationOptions::DB])
            ->expectsOutput('Total: 4')
            ->assertSuccessful();
    });

    it('filters by locale', function () {
        $this->artisan('translation-handler:list', [
            'from' => TranslationOptions::DB,
            '--locale' => 'it',
        ])
            ->expectsOutput('Total: 2')
            ->assertSuccessful();
    });
})->group('ListCommand', 'DatabaseHandler');
