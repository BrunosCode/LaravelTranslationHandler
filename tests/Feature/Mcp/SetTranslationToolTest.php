<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Mcp\Tools\SetTranslationTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

uses(RefreshDatabase::class);

describe('SetTranslationTool php', function () {
    beforeEach(function () {
        $this->tool = new SetTranslationTool;
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('writes a new translation', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'key' => 'test1.new', 'locale' => 'en', 'value' => 'New Value']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(
            TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.new')->whereLocale('en')->first()?->value
        )->toBe('New Value');
    });

    it('does not overwrite existing translation without force', function () {
        $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'key' => 'test1.get', 'locale' => 'en', 'value' => 'Overwritten']));

        expect(
            TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.get')->whereLocale('en')->first()?->value
        )->toBe('get-1-en');
    });

    it('overwrites existing translation with force', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'key' => 'test1.get', 'locale' => 'en', 'value' => 'Overwritten', 'force' => true]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(
            TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.get')->whereLocale('en')->first()?->value
        )->toBe('Overwritten');
    });

    it('writes translation for italian locale', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'key' => 'test1.nuovo', 'locale' => 'it', 'value' => 'Nuovo valore']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(
            TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.nuovo')->whereLocale('it')->first()?->value
        )->toBe('Nuovo valore');
    });
})->group('Mcp', 'SetTranslationTool', 'PhpFileHandler');

describe('SetTranslationTool csv', function () {
    beforeEach(function () {
        $this->tool = new SetTranslationTool;
    });

    afterEach(function () {
        $this->cleanCsvTranslations();
    });

    it('writes a new translation', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::CSV, 'key' => 'test1.new', 'locale' => 'en', 'value' => 'New CSV Value']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(
            TranslationHandler::get(TranslationOptions::CSV)->whereKey('test1.new')->whereLocale('en')->first()?->value
        )->toBe('New CSV Value');
    });
})->group('Mcp', 'SetTranslationTool', 'CsvFileHandler');

describe('SetTranslationTool database', function () {
    beforeEach(function () {
        $this->tool = new SetTranslationTool;
        $this->prepareDbTranslations();
    });

    it('writes a new translation', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::DB, 'key' => 'test1.new', 'locale' => 'en', 'value' => 'New DB Value']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(
            TranslationHandler::get(TranslationOptions::DB)->whereKey('test1.new')->whereLocale('en')->first()?->value
        )->toBe('New DB Value');
    });

    it('overwrites existing translation with force', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::DB, 'key' => 'test1.get', 'locale' => 'en', 'value' => 'Overwritten', 'force' => true]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(
            TranslationHandler::get(TranslationOptions::DB)->whereKey('test1.get')->whereLocale('en')->first()?->value
        )->toBe('Overwritten');
    });
})->group('Mcp', 'SetTranslationTool', 'DatabaseHandler');

describe('SetTranslationTool errors', function () {
    beforeEach(function () {
        $this->tool = new SetTranslationTool;
    });

    it('returns an error response for invalid format', function () {
        $response = $this->tool->handle(new Request(['format' => 'invalid_format', 'key' => 'test1.get', 'locale' => 'en', 'value' => 'Value']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });
})->group('Mcp', 'SetTranslationTool');
