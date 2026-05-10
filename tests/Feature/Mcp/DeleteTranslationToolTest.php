<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Mcp\Tools\DeleteTranslationTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

uses(RefreshDatabase::class);

describe('DeleteTranslationTool php', function () {
    beforeEach(function () {
        $this->tool = new DeleteTranslationTool;
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('deletes a key for all locales', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'key' => 'test1.get']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.get'))->toHaveCount(0);
    });

    it('deletes a key for a specific locale', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'key' => 'test1.get', 'locale' => 'en']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.get')->whereLocale('en'))->toHaveCount(0);
        expect(TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.get')->whereLocale('it'))->not->toHaveCount(0);
    });

    it('returns deleted=false when key does not exist', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'key' => 'nonexistent.key']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        $payload = json_decode((string) $response->content(), true);
        expect($payload['deleted'])->toBeFalse();
        expect($payload['count'])->toBe(0);
    });
})->group('Mcp', 'DeleteTranslationTool', 'PhpFileHandler');

describe('DeleteTranslationTool json', function () {
    beforeEach(function () {
        $this->tool = new DeleteTranslationTool;
        $this->prepareJsonTranslations();
    });

    afterEach(function () {
        $this->cleanJsonTranslations();
    });

    it('deletes a key for all locales', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::JSON, 'key' => 'test1.get']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(TranslationHandler::get(TranslationOptions::JSON)->whereKey('test1.get'))->toHaveCount(0);
    });
})->group('Mcp', 'DeleteTranslationTool', 'JsonFileHandler');

describe('DeleteTranslationTool database', function () {
    beforeEach(function () {
        $this->tool = new DeleteTranslationTool;
        $this->prepareDbTranslations();
    });

    afterEach(function () {
        $this->cleanDbTranslations();
    });

    it('deletes a key for all locales', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::DB, 'key' => 'test1.get']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(TranslationHandler::get(TranslationOptions::DB)->whereKey('test1.get'))->toHaveCount(0);
    });
})->group('Mcp', 'DeleteTranslationTool', 'DatabaseHandler');

describe('DeleteTranslationTool errors', function () {
    beforeEach(function () {
        $this->tool = new DeleteTranslationTool;
    });

    it('returns an error response for invalid format', function () {
        $response = $this->tool->handle(new Request(['format' => 'invalid_format', 'key' => 'test1.get']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });
})->group('Mcp', 'DeleteTranslationTool');
