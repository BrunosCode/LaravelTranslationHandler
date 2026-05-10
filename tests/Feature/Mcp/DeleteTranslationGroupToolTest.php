<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Mcp\Tools\DeleteTranslationGroupTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

uses(RefreshDatabase::class);

describe('DeleteTranslationGroupTool php', function () {
    beforeEach(function () {
        $this->tool = new DeleteTranslationGroupTool;
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('deletes all keys in a group', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'group' => 'test1']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(TranslationHandler::get(TranslationOptions::PHP)->filter(fn ($t) => str_starts_with($t->key, 'test1.')))->toHaveCount(0);

        $payload = json_decode((string) $response->content(), true);
        expect($payload['deleted'])->toBeTrue();
        expect($payload['count'])->toBeGreaterThan(0);
    });

    it('returns deleted=false when group has no translations', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'group' => 'nonexistent']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        $payload = json_decode((string) $response->content(), true);
        expect($payload['deleted'])->toBeFalse();
        expect($payload['count'])->toBe(0);
    });
})->group('Mcp', 'DeleteTranslationGroupTool', 'PhpFileHandler');

describe('DeleteTranslationGroupTool json', function () {
    beforeEach(function () {
        $this->tool = new DeleteTranslationGroupTool;
        $this->prepareJsonTranslations();
    });

    afterEach(function () {
        $this->cleanJsonTranslations();
    });

    it('deletes all keys in a group', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::JSON, 'group' => 'test1']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(TranslationHandler::get(TranslationOptions::JSON)->filter(fn ($t) => str_starts_with($t->key, 'test1.')))->toHaveCount(0);
    });
})->group('Mcp', 'DeleteTranslationGroupTool', 'JsonFileHandler');

describe('DeleteTranslationGroupTool database', function () {
    beforeEach(function () {
        $this->tool = new DeleteTranslationGroupTool;
        $this->prepareDbTranslations();
    });

    afterEach(function () {
        $this->cleanDbTranslations();
    });

    it('deletes all keys in a group', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::DB, 'group' => 'test1']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect(TranslationHandler::get(TranslationOptions::DB)->filter(fn ($t) => str_starts_with($t->key, 'test1.')))->toHaveCount(0);
    });
})->group('Mcp', 'DeleteTranslationGroupTool', 'DatabaseHandler');

describe('DeleteTranslationGroupTool errors', function () {
    beforeEach(function () {
        $this->tool = new DeleteTranslationGroupTool;
    });

    it('returns an error response for invalid format', function () {
        $response = $this->tool->handle(new Request(['format' => 'invalid_format', 'group' => 'test1']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });
})->group('Mcp', 'DeleteTranslationGroupTool');
