<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Mcp\Tools\CheckTranslationsTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

uses(RefreshDatabase::class);

function prepareCheckToolSource(string $contents, string $filename = 'Source.php'): string
{
    $dir = storage_path('check-tool-test');

    if (! File::exists($dir)) {
        File::makeDirectory($dir, 0777, true);
    }

    File::put("{$dir}/{$filename}", $contents);

    TranslationHandler::setOption('check', [
        'backend' => ['paths' => [$dir], 'extensions' => ['php']],
        'frontend' => ['paths' => [], 'extensions' => ['ts', 'tsx', 'js', 'jsx']],
    ]);

    return $dir;
}

describe('CheckTranslationsTool php', function () {
    beforeEach(function () {
        $this->tool = new CheckTranslationsTool;
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
        File::deleteDirectory(storage_path('check-tool-test'));
    });

    it('passes when every referenced key is defined', function () {
        prepareCheckToolSource(<<<'PHP'
        <?php
        echo __('test1.get');
        echo __('test2.get');
        PHP);

        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'side' => 'backend',
        ]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $payload = json_decode((string) $response->content(), true);
        expect($payload['passed'])->toBeTrue();
        expect($payload['totalMissing'])->toBe(0);
    });

    it('reports a missing key per locale', function () {
        prepareCheckToolSource(<<<'PHP'
        <?php
        echo __('test1.does.not.exist');
        PHP);

        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'side' => 'backend',
        ]));

        expect($response->isError())->toBeFalse();

        $payload = json_decode((string) $response->content(), true);
        expect($payload['passed'])->toBeFalse();
        // one missing key in each configured locale (en, it)
        expect($payload['totalMissing'])->toBe(2);
        expect($payload['sides']['backend']['locales']['en']['keys'])->toContain('test1.does.not.exist');
        expect($payload['orphans'])->toBeNull();
    });

    it('reports orphans when requested', function () {
        prepareCheckToolSource(<<<'PHP'
        <?php
        echo __('test1.get');
        PHP);

        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'side' => 'backend',
            'orphans' => true,
        ]));

        expect($response->isError())->toBeFalse();

        $payload = json_decode((string) $response->content(), true);
        expect($payload['orphans']['en'])->toContain('test2.get');
    });

    it('restricts the report to the requested locale', function () {
        prepareCheckToolSource(<<<'PHP'
        <?php
        echo __('test1.get');
        PHP);

        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'side' => 'backend',
            'locales' => ['en'],
        ]));

        expect($response->isError())->toBeFalse();

        $payload = json_decode((string) $response->content(), true);
        expect($payload['locales'])->toBe(['en']);
        expect(array_keys($payload['sides']['backend']['locales']))->toBe(['en']);
    });
})->group('Mcp', 'CheckTranslationsTool', 'PhpFileHandler');

describe('CheckTranslationsTool errors', function () {
    beforeEach(function () {
        $this->tool = new CheckTranslationsTool;
    });

    it('returns an error response for invalid format', function () {
        $response = $this->tool->handle(new Request(['format' => 'invalid_format']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });
})->group('Mcp', 'CheckTranslationsTool');
