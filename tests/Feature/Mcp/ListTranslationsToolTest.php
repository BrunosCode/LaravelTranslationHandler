<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Mcp\Tools\ListTranslationsTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

uses(RefreshDatabase::class);

describe('ListTranslationsTool php', function () {
    beforeEach(function () {
        $this->tool = new ListTranslationsTool;
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('returns all translations', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(TranslationHandler::get(TranslationOptions::PHP))->not->toBeEmpty();
    });

    it('filters by locale', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'locale' => 'en']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $collection = TranslationHandler::get(TranslationOptions::PHP)->whereLocale('en');
        expect($collection)->not->toBeEmpty();
        expect($collection->every(fn ($t) => $t->locale === 'en'))->toBeTrue();
    });

    it('filters by group', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'group' => 'test1']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $collection = TranslationHandler::get(TranslationOptions::PHP)->whereGroup('test1');
        expect($collection)->not->toBeEmpty();
        expect($collection->every(fn ($t) => str_starts_with($t->key, 'test1.')))->toBeTrue();
    });

    it('combines locale and group filters', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'locale' => 'it', 'group' => 'test1']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $collection = TranslationHandler::get(TranslationOptions::PHP)->whereLocale('it')->whereGroup('test1');
        expect($collection->every(fn ($t) => $t->locale === 'it' && str_starts_with($t->key, 'test1.')))->toBeTrue();
    });
})->group('Mcp', 'ListTranslationsTool', 'PhpFileHandler');

describe('ListTranslationsTool json', function () {
    beforeEach(function () {
        $this->tool = new ListTranslationsTool;
        $this->prepareJsonTranslations();
    });

    afterEach(function () {
        $this->cleanJsonTranslations();
    });

    it('returns all translations', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::JSON]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(TranslationHandler::get(TranslationOptions::JSON))->not->toBeEmpty();
    });
})->group('Mcp', 'ListTranslationsTool', 'JsonFileHandler');

describe('ListTranslationsTool csv', function () {
    beforeEach(function () {
        $this->tool = new ListTranslationsTool;
        $this->prepareCsvTranslations();
    });

    afterEach(function () {
        $this->cleanCsvTranslations();
    });

    it('returns all translations', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::CSV]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(TranslationHandler::get(TranslationOptions::CSV))->not->toBeEmpty();
    });
})->group('Mcp', 'ListTranslationsTool', 'CsvFileHandler');

describe('ListTranslationsTool database', function () {
    beforeEach(function () {
        $this->tool = new ListTranslationsTool;
        $this->prepareDbTranslations();
    });

    it('returns all translations', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::DB]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(TranslationHandler::get(TranslationOptions::DB))->not->toBeEmpty();
    });
})->group('Mcp', 'ListTranslationsTool', 'DatabaseHandler');

describe('ListTranslationsTool errors', function () {
    beforeEach(function () {
        $this->tool = new ListTranslationsTool;
    });

    it('returns an error response for invalid format', function () {
        $response = $this->tool->handle(new Request(['format' => 'invalid_format']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });
})->group('Mcp', 'ListTranslationsTool');
