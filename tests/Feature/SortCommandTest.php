<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('SortCommand common', function () {
    it('asks questions if no arguments are provided', function () {
        $this->artisan('translation-handler:sort')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP);
    });

    it('throws error if no from argument is provided', function () {
        $this->artisan('translation-handler:sort')
            ->expectsQuestion('From where do you want to import translations?', '');
    })->throws(InvalidArgumentException::class);

    it('rejects db format', function () {
        $this->artisan('translation-handler:sort')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::DB);
    })->throws(InvalidArgumentException::class);
})->group('SortCommand');

describe('SortCommand php', function () {
    beforeEach(function () {
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('sorts all translations', function () {
        $this->artisan('translation-handler:sort', [
            'from' => TranslationOptions::PHP,
        ])
            ->assertSuccessful();

        $collection = TranslationHandler::get(TranslationOptions::PHP);
        foreach (config('translation-handler.locales', ['en', 'it']) as $locale) {
            $keys = $collection->whereLocale($locale)->pluck('key')->values()->toArray();
            expect($keys)->toBe(collect($keys)->sort()->values()->toArray());
        }
    });

    it('sorts only a specific locale', function () {
        $this->artisan('translation-handler:sort', [
            'from' => TranslationOptions::PHP,
            '--locale' => ['en'],
        ])
            ->assertSuccessful();

        $keys = TranslationHandler::get(TranslationOptions::PHP)->whereLocale('en')->pluck('key')->values()->toArray();
        expect($keys)->toBe(collect($keys)->sort()->values()->toArray());
    });

    it('sorts only a specific group', function () {
        $this->artisan('translation-handler:sort', [
            'from' => TranslationOptions::PHP,
            '--group' => ['test1'],
        ])
            ->assertSuccessful();

        $collection = TranslationHandler::get(TranslationOptions::PHP)->whereGroup('test1');
        foreach (config('translation-handler.locales', ['en', 'it']) as $locale) {
            $keys = $collection->whereLocale($locale)->pluck('key')->values()->toArray();
            expect($keys)->toBe(collect($keys)->sort()->values()->toArray());
        }
    });

    it('fails when no translations match filters', function () {
        $this->artisan('translation-handler:sort', [
            'from' => TranslationOptions::PHP,
            '--group' => ['nonexistent'],
        ])
            ->expectsOutput('No translations found to sort!')
            ->assertFailed();
    });
})->group('SortCommand', 'PhpFileHandler');

describe('SortCommand json', function () {
    beforeEach(function () {
        $this->prepareJsonTranslations();
    });

    afterEach(function () {
        $this->cleanJsonTranslations();
    });

    it('sorts all translations', function () {
        $this->artisan('translation-handler:sort', [
            'from' => TranslationOptions::JSON,
        ])
            ->assertSuccessful();

        $collection = TranslationHandler::get(TranslationOptions::JSON);
        foreach (config('translation-handler.locales', ['en', 'it']) as $locale) {
            $keys = $collection->whereLocale($locale)->pluck('key')->values()->toArray();
            expect($keys)->toBe(collect($keys)->sort()->values()->toArray());
        }
    });
})->group('SortCommand', 'JsonFileHandler');

describe('SortCommand csv', function () {
    beforeEach(function () {
        $this->prepareCsvTranslations();
    });

    afterEach(function () {
        $this->cleanCsvTranslations();
    });

    it('sorts all translations', function () {
        $this->artisan('translation-handler:sort', [
            'from' => TranslationOptions::CSV,
        ])
            ->assertSuccessful();

        $keys = TranslationHandler::get(TranslationOptions::CSV)->pluck('key')->values()->toArray();
        $sorted = $keys;
        sort($sorted);
        expect($keys)->toBe($sorted);
    });
})->group('SortCommand', 'CsvFileHandler');
