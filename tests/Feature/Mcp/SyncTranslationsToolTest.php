<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Mcp\Tools\SyncTranslationsTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

uses(RefreshDatabase::class);

describe('SyncTranslationsTool php to json', function () {
    beforeEach(function () {
        $this->tool = new SyncTranslationsTool;
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
        $this->cleanJsonTranslations();
    });

    it('syncs all translations from php to json', function () {
        $response = $this->tool->handle(new Request(['from' => TranslationOptions::PHP, 'to' => TranslationOptions::JSON]));

        expect($response)->toBeInstanceOf(ResponseFactory::class);

        $phpCount = TranslationHandler::get(TranslationOptions::PHP)->count();
        $jsonCount = TranslationHandler::get(TranslationOptions::JSON)->count();
        expect($jsonCount)->toBeGreaterThanOrEqual($phpCount);
    });

    it('does not overwrite existing json translations without force', function () {
        $this->prepareJsonTranslations();

        $originalValue = TranslationHandler::get(TranslationOptions::JSON)
            ->whereKey('test1.get')->whereLocale('en')->first()?->value;

        $this->tool->handle(new Request(['from' => TranslationOptions::PHP, 'to' => TranslationOptions::JSON, 'force' => false]));

        expect(
            TranslationHandler::get(TranslationOptions::JSON)->whereKey('test1.get')->whereLocale('en')->first()?->value
        )->toBe($originalValue);
    });
})->group('Mcp', 'SyncTranslationsTool', 'PhpFileHandler', 'JsonFileHandler');

describe('SyncTranslationsTool json to php', function () {
    beforeEach(function () {
        $this->tool = new SyncTranslationsTool;
        $this->prepareJsonTranslations();
    });

    afterEach(function () {
        $this->cleanJsonTranslations();
        $this->cleanPhpTranslations();
    });

    it('syncs all translations from json to php', function () {
        $response = $this->tool->handle(new Request(['from' => TranslationOptions::JSON, 'to' => TranslationOptions::PHP]));

        expect($response)->toBeInstanceOf(ResponseFactory::class);
        expect(TranslationHandler::get(TranslationOptions::PHP))->not->toBeEmpty();
    });
})->group('Mcp', 'SyncTranslationsTool', 'JsonFileHandler', 'PhpFileHandler');

describe('SyncTranslationsTool php to database', function () {
    beforeEach(function () {
        $this->tool = new SyncTranslationsTool;
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('syncs all translations from php to database', function () {
        $response = $this->tool->handle(new Request(['from' => TranslationOptions::PHP, 'to' => TranslationOptions::DB]));

        expect($response)->toBeInstanceOf(ResponseFactory::class);
        expect(TranslationHandler::get(TranslationOptions::DB))->not->toBeEmpty();
    });
})->group('Mcp', 'SyncTranslationsTool', 'PhpFileHandler', 'DatabaseHandler');

describe('SyncTranslationsTool errors', function () {
    beforeEach(function () {
        $this->tool = new SyncTranslationsTool;
    });

    it('returns an error when source and destination are the same', function () {
        $response = $this->tool->handle(new Request(['from' => TranslationOptions::PHP, 'to' => TranslationOptions::PHP]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });
})->group('Mcp', 'SyncTranslationsTool');
