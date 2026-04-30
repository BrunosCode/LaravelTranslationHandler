<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Mcp\Tools\FindTranslationTool;
use BrunosCode\TranslationHandler\Mcp\Tools\GetTranslationConfigTool;
use BrunosCode\TranslationHandler\Mcp\Tools\ListTranslationGroupsTool;
use BrunosCode\TranslationHandler\Mcp\Tools\ListTranslationsTool;
use BrunosCode\TranslationHandler\Mcp\Tools\SetAllLocalesTranslationTool;
use BrunosCode\TranslationHandler\Mcp\Tools\SetTranslationTool;
use BrunosCode\TranslationHandler\Mcp\Tools\SyncTranslationsTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

uses(RefreshDatabase::class);

/**
 * laravel/boost executes tools in a subprocess via boost:execute-tool. That command
 * assumes $tool->handle($request) returns a Response and calls ->isError() directly
 * on the result — passing a ResponseFactory triggers a BadMethodCallException.
 *
 * These tests simulate that contract: each tool's handle() must return a Response
 * (not a ResponseFactory) and isError() must be callable on it.
 */
describe('Boost ExecuteToolCommand compatibility', function () {
    beforeEach(function () {
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('GetTranslationConfigTool returns Response with callable isError()', function () {
        $response = (new GetTranslationConfigTool)->handle(new Request);

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
    });

    it('ListTranslationsTool returns Response with callable isError()', function () {
        $response = (new ListTranslationsTool)->handle(new Request(['format' => TranslationOptions::PHP]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
    });

    it('ListTranslationGroupsTool returns Response with callable isError()', function () {
        $response = (new ListTranslationGroupsTool)->handle(new Request(['format' => TranslationOptions::PHP]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
    });

    it('FindTranslationTool returns Response with callable isError()', function () {
        $response = (new FindTranslationTool)->handle(new Request(['format' => TranslationOptions::PHP, 'key' => 'test1.get', 'locale' => 'en']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
    });

    it('SetTranslationTool returns Response with callable isError()', function () {
        $response = (new SetTranslationTool)->handle(new Request([
            'format' => TranslationOptions::PHP,
            'key' => 'test1.boost',
            'locale' => 'en',
            'value' => 'boost',
        ]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
    });

    it('SetAllLocalesTranslationTool returns Response with callable isError()', function () {
        $response = (new SetAllLocalesTranslationTool)->handle(new Request([
            'format' => TranslationOptions::PHP,
            'key' => 'test1.boost_all',
            'values' => ['en' => 'EN', 'it' => 'IT'],
        ]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
    });

    it('SyncTranslationsTool returns Response with callable isError()', function () {
        $response = (new SyncTranslationsTool)->handle(new Request([
            'from' => TranslationOptions::PHP,
            'to' => TranslationOptions::DB,
            'force' => true,
        ]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
    });

    it('all tools return text content with valid JSON payload', function () {
        $tools = [
            [new GetTranslationConfigTool, new Request],
            [new ListTranslationsTool, new Request(['format' => TranslationOptions::PHP])],
            [new ListTranslationGroupsTool, new Request(['format' => TranslationOptions::PHP])],
            [new FindTranslationTool, new Request(['format' => TranslationOptions::PHP, 'key' => 'test1.get', 'locale' => 'en'])],
        ];

        foreach ($tools as [$tool, $request]) {
            $response = $tool->handle($request);
            $text = (string) $response->content();

            expect(json_decode($text, true))->toBeArray();
        }
    });
})->group('Mcp', 'BoostCompatibility');
