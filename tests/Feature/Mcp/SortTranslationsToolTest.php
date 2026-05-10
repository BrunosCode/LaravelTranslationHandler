<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Mcp\Tools\SortTranslationsTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

uses(RefreshDatabase::class);

describe('SortTranslationsTool php', function () {
    beforeEach(function () {
        $this->tool = new SortTranslationsTool;
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('sorts all translations', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $collection = TranslationHandler::get(TranslationOptions::PHP);
        foreach (config('translation-handler.locales', ['en', 'it']) as $locale) {
            $keys = $collection->whereLocale($locale)->pluck('key')->values()->toArray();
            expect($keys)->toBe(collect($keys)->sort()->values()->toArray());
        }
    });

    it('sorts only specific locales', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'locales' => ['en']]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $keys = TranslationHandler::get(TranslationOptions::PHP)->whereLocale('en')->pluck('key')->values()->toArray();
        expect($keys)->toBe(collect($keys)->sort()->values()->toArray());
    });

    it('sorts only specific groups', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'groups' => ['test1']]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $collection = TranslationHandler::get(TranslationOptions::PHP)->whereGroup('test1');
        foreach (config('translation-handler.locales', ['en', 'it']) as $locale) {
            $keys = $collection->whereLocale($locale)->pluck('key')->values()->toArray();
            expect($keys)->toBe(collect($keys)->sort()->values()->toArray());
        }
    });

    it('returns sorted=false when no translations match', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'groups' => ['nonexistent']]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        $payload = json_decode((string) $response->content(), true);
        expect($payload['sorted'])->toBeFalse();
        expect($payload['count'])->toBe(0);
    });
})->group('Mcp', 'SortTranslationsTool', 'PhpFileHandler');

describe('SortTranslationsTool json', function () {
    beforeEach(function () {
        $this->tool = new SortTranslationsTool;
        $this->prepareJsonTranslations();
    });

    afterEach(function () {
        $this->cleanJsonTranslations();
    });

    it('sorts all translations', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::JSON]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
    });
})->group('Mcp', 'SortTranslationsTool', 'JsonFileHandler');

describe('SortTranslationsTool csv', function () {
    beforeEach(function () {
        $this->tool = new SortTranslationsTool;
        $this->prepareCsvTranslations();
    });

    afterEach(function () {
        $this->cleanCsvTranslations();
    });

    it('sorts all translations', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::CSV]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
    });
})->group('Mcp', 'SortTranslationsTool', 'CsvFileHandler');

describe('SortTranslationsTool errors', function () {
    beforeEach(function () {
        $this->tool = new SortTranslationsTool;
    });

    it('returns an error response for invalid format', function () {
        $response = $this->tool->handle(new Request(['format' => 'invalid_format']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });
})->group('Mcp', 'SortTranslationsTool');
