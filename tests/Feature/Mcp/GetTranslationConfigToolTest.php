<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Mcp\Tools\GetTranslationConfigTool;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;

describe('GetTranslationConfigTool', function () {
    beforeEach(function () {
        $this->tool = new GetTranslationConfigTool;
    });

    it('returns a structured response', function () {
        $response = $this->tool->handle(new Request);

        expect($response)->toBeInstanceOf(ResponseFactory::class);
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
