<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Mcp\Tools\SetAllLocalesTranslationTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

uses(RefreshDatabase::class);

describe('SetAllLocalesTranslationTool php', function () {
    beforeEach(function () {
        $this->tool = new SetAllLocalesTranslationTool;
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('writes translations for all provided locales', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'key' => 'test1.new',
            'values' => ['en' => 'New EN', 'it' => 'Nuovo IT'],
        ]));

        expect($response)->toBeInstanceOf(ResponseFactory::class);

        expect(
            TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.new')->whereLocale('en')->first()?->value
        )->toBe('New EN');

        expect(
            TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.new')->whereLocale('it')->first()?->value
        )->toBe('Nuovo IT');
    });

    it('does not overwrite existing translations without force', function () {
        $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'key' => 'test1.get',
            'values' => ['en' => 'Overwritten EN', 'it' => 'Soprascritto IT'],
        ]));

        expect(
            TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.get')->whereLocale('en')->first()?->value
        )->toBe('get-1-en');

        expect(
            TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.get')->whereLocale('it')->first()?->value
        )->toBe('get-1-it');
    });

    it('overwrites existing translations with force', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'key' => 'test1.get',
            'values' => ['en' => 'Overwritten EN', 'it' => 'Soprascritto IT'],
            'force' => true,
        ]));

        expect($response)->toBeInstanceOf(ResponseFactory::class);

        expect(
            TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.get')->whereLocale('en')->first()?->value
        )->toBe('Overwritten EN');

        expect(
            TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.get')->whereLocale('it')->first()?->value
        )->toBe('Soprascritto IT');
    });
})->group('Mcp', 'SetAllLocalesTranslationTool', 'PhpFileHandler');

describe('SetAllLocalesTranslationTool csv', function () {
    beforeEach(function () {
        $this->tool = new SetAllLocalesTranslationTool;
    });

    afterEach(function () {
        $this->cleanCsvTranslations();
    });

    it('writes translations for all provided locales', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::CSV,
            'key' => 'test1.new',
            'values' => ['en' => 'New CSV EN', 'it' => 'Nuovo CSV IT'],
        ]));

        expect($response)->toBeInstanceOf(ResponseFactory::class);

        expect(
            TranslationHandler::get(TranslationOptions::CSV)->whereKey('test1.new')->whereLocale('en')->first()?->value
        )->toBe('New CSV EN');
    });
})->group('Mcp', 'SetAllLocalesTranslationTool', 'CsvFileHandler');

describe('SetAllLocalesTranslationTool database', function () {
    beforeEach(function () {
        $this->tool = new SetAllLocalesTranslationTool;
        $this->prepareDbTranslations();
    });

    it('writes translations for all provided locales', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::DB,
            'key' => 'test1.new',
            'values' => ['en' => 'New DB EN', 'it' => 'Nuovo DB IT'],
        ]));

        expect($response)->toBeInstanceOf(ResponseFactory::class);

        expect(
            TranslationHandler::get(TranslationOptions::DB)->whereKey('test1.new')->whereLocale('en')->first()?->value
        )->toBe('New DB EN');
    });
})->group('Mcp', 'SetAllLocalesTranslationTool', 'DatabaseHandler');

describe('SetAllLocalesTranslationTool errors', function () {
    beforeEach(function () {
        $this->tool = new SetAllLocalesTranslationTool;
    });

    it('returns an error for invalid format', function () {
        $response = $this->tool->handle(new Request([
            'format' => 'invalid_format',
            'key' => 'test1.get',
            'values' => ['en' => 'Hello'],
        ]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });

    it('returns an error for empty values', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'key' => 'test1.get',
            'values' => [],
        ]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });

    it('returns an error for missing values', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'key' => 'test1.get',
        ]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });
})->group('Mcp', 'SetAllLocalesTranslationTool');
