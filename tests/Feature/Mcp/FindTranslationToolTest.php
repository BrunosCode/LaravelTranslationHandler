<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Mcp\Tools\FindTranslationTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

uses(RefreshDatabase::class);

describe('FindTranslationTool php', function () {
    beforeEach(function () {
        $this->tool = new FindTranslationTool;
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('returns structured response when translation exists', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'key' => 'test1.get', 'locale' => 'en']));

        expect($response)->toBeInstanceOf(ResponseFactory::class);
    });

    it('returns structured response when key does not exist', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'key' => 'does.not.exist', 'locale' => 'en']));

        expect($response)->toBeInstanceOf(ResponseFactory::class);
    });

    it('returns structured response when locale does not exist', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'key' => 'test1.get', 'locale' => 'fr']));

        expect($response)->toBeInstanceOf(ResponseFactory::class);
    });

    it('finds nested keys', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'key' => 'test1.nested.get', 'locale' => 'en']));

        expect($response)->toBeInstanceOf(ResponseFactory::class);
    });

    it('finds translations for italian locale', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'key' => 'test1.get', 'locale' => 'it']));

        expect($response)->toBeInstanceOf(ResponseFactory::class);
    });
})->group('Mcp', 'FindTranslationTool', 'PhpFileHandler');

describe('FindTranslationTool json', function () {
    beforeEach(function () {
        $this->tool = new FindTranslationTool;
        $this->prepareJsonTranslations();
    });

    afterEach(function () {
        $this->cleanJsonTranslations();
    });

    it('returns structured response when translation exists', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::JSON, 'key' => 'test1.get', 'locale' => 'en']));

        expect($response)->toBeInstanceOf(ResponseFactory::class);
    });

    it('returns structured response when key does not exist', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::JSON, 'key' => 'missing.key', 'locale' => 'en']));

        expect($response)->toBeInstanceOf(ResponseFactory::class);
    });
})->group('Mcp', 'FindTranslationTool', 'JsonFileHandler');

describe('FindTranslationTool database', function () {
    beforeEach(function () {
        $this->tool = new FindTranslationTool;
        $this->prepareDbTranslations();
    });

    it('returns structured response when translation exists', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::DB, 'key' => 'test1.get', 'locale' => 'en']));

        expect($response)->toBeInstanceOf(ResponseFactory::class);
    });

    it('returns structured response when key does not exist', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::DB, 'key' => 'missing.key', 'locale' => 'en']));

        expect($response)->toBeInstanceOf(ResponseFactory::class);
    });
})->group('Mcp', 'FindTranslationTool', 'DatabaseHandler');

describe('FindTranslationTool errors', function () {
    beforeEach(function () {
        $this->tool = new FindTranslationTool;
    });

    it('returns an error response for invalid format', function () {
        $response = $this->tool->handle(new Request(['format' => 'invalid_format', 'key' => 'test1.get', 'locale' => 'en']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });
})->group('Mcp', 'FindTranslationTool');
