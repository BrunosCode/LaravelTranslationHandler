<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DeleteGroupCommand common', function () {
    it('asks questions if no arguments are provided', function () {
        $this->artisan('translation-handler:delete-group')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation group?', 'test1');
    });

    it('throws error if no from argument is provided', function () {
        $this->artisan('translation-handler:delete-group')
            ->expectsQuestion('From where do you want to import translations?', '');
    })->throws(InvalidArgumentException::class);

    it('throws error if no group argument is provided', function () {
        $this->artisan('translation-handler:delete-group')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation group?', '');
    })->throws(InvalidArgumentException::class);
})->group('DeleteGroupCommand');

describe('DeleteGroupCommand php', function () {
    beforeEach(function () {
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('deletes all keys in a group', function () {
        $before = TranslationHandler::get(TranslationOptions::PHP)->filter(fn ($t) => str_starts_with($t->key, 'test1.'))->count();
        expect($before)->toBeGreaterThan(0);

        $this->artisan('translation-handler:delete-group', [
            'from' => TranslationOptions::PHP,
            'group' => 'test1',
        ])
            ->assertSuccessful();

        expect(TranslationHandler::get(TranslationOptions::PHP)->filter(fn ($t) => str_starts_with($t->key, 'test1.')))->toHaveCount(0);
    });

    it('fails when group has no translations', function () {
        $this->artisan('translation-handler:delete-group', [
            'from' => TranslationOptions::PHP,
            'group' => 'nonexistent',
        ])
            ->expectsOutput('No translations found for group!')
            ->assertFailed();
    });
})->group('DeleteGroupCommand', 'PhpFileHandler');

describe('DeleteGroupCommand json', function () {
    beforeEach(function () {
        $this->prepareJsonTranslations();
    });

    afterEach(function () {
        $this->cleanJsonTranslations();
    });

    it('deletes all keys in a group', function () {
        $this->artisan('translation-handler:delete-group', [
            'from' => TranslationOptions::JSON,
            'group' => 'test1',
        ])
            ->assertSuccessful();

        expect(TranslationHandler::get(TranslationOptions::JSON)->filter(fn ($t) => str_starts_with($t->key, 'test1.')))->toHaveCount(0);
    });

    it('fails when group has no translations', function () {
        $this->artisan('translation-handler:delete-group', [
            'from' => TranslationOptions::JSON,
            'group' => 'nonexistent',
        ])
            ->expectsOutput('No translations found for group!')
            ->assertFailed();
    });
})->group('DeleteGroupCommand', 'JsonFileHandler');

describe('DeleteGroupCommand csv', function () {
    beforeEach(function () {
        $this->prepareCsvTranslations();
    });

    afterEach(function () {
        $this->cleanCsvTranslations();
    });

    it('deletes all keys in a group', function () {
        $this->artisan('translation-handler:delete-group', [
            'from' => TranslationOptions::CSV,
            'group' => 'test1',
        ])
            ->assertSuccessful();

        expect(TranslationHandler::get(TranslationOptions::CSV)->filter(fn ($t) => str_starts_with($t->key, 'test1.')))->toHaveCount(0);
    });

    it('fails when group has no translations', function () {
        $this->artisan('translation-handler:delete-group', [
            'from' => TranslationOptions::CSV,
            'group' => 'nonexistent',
        ])
            ->expectsOutput('No translations found for group!')
            ->assertFailed();
    });
})->group('DeleteGroupCommand', 'CsvFileHandler');

describe('DeleteGroupCommand database', function () {
    beforeEach(function () {
        $this->prepareDbTranslations();
    });

    afterEach(function () {
        $this->cleanDbTranslations();
    });

    it('deletes all keys in a group', function () {
        $this->artisan('translation-handler:delete-group', [
            'from' => TranslationOptions::DB,
            'group' => 'test1',
        ])
            ->assertSuccessful();

        expect(TranslationHandler::get(TranslationOptions::DB)->filter(fn ($t) => str_starts_with($t->key, 'test1.')))->toHaveCount(0);
    });

    it('fails when group has no translations', function () {
        $this->artisan('translation-handler:delete-group', [
            'from' => TranslationOptions::DB,
            'group' => 'nonexistent',
        ])
            ->expectsOutput('No translations found for group!')
            ->assertFailed();
    });
})->group('DeleteGroupCommand', 'DatabaseHandler');
