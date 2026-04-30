<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Mcp\Tools\GetTranslationConfigTool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

describe('GetTranslationConfigTool', function () {
    beforeEach(function () {
        $this->tool = new GetTranslationConfigTool;
    });

    it('returns a structured response', function () {
        $response = $this->tool->handle(new Request);

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $data = json_decode((string) $response->content(), true);
        expect($data)->toBeArray();
        expect($data)->toHaveKey('locales');
    });

    it('reads locales from config', function () {
        expect(TranslationHandler::getOption('locales'))->toBe(['en', 'it']);
    });

    it('reads file names from config', function () {
        expect(TranslationHandler::getOption('fileNames'))->toBe(['test1', 'test2']);
    });

    it('reads key delimiter from config', function () {
        expect(TranslationHandler::getOption('keyDelimiter'))->toBe('.');
    });

    it('reads default import/export formats from config', function () {
        expect(TranslationHandler::getOption('defaultImportFrom'))->toBe(TranslationOptions::PHP);
        expect(TranslationHandler::getOption('defaultImportTo'))->toBe(TranslationOptions::JSON);
        expect(TranslationHandler::getOption('defaultExportFrom'))->toBe(TranslationOptions::JSON);
        expect(TranslationHandler::getOption('defaultExportTo'))->toBe(TranslationOptions::PHP);
    });
})->group('Mcp', 'GetTranslationConfigTool');
