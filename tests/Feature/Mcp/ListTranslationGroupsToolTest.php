<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Mcp\Tools\ListTranslationGroupsTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

uses(RefreshDatabase::class);

describe('ListTranslationGroupsTool php', function () {
    beforeEach(function () {
        $this->tool = new ListTranslationGroupsTool;
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('returns level-0 groups by default', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $data = json_decode((string) $response->content(), true);
        expect($data['level'])->toBe(0);
    });

    it('returns correct level-0 groups', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'level' => 0]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $data = json_decode((string) $response->content(), true);

        expect($data['level'])->toBe(0);
        expect($data['groups'])->toContain('test1');
        expect($data['groups'])->toContain('test2');
        expect($data['total'])->toBeGreaterThanOrEqual(2);

        foreach ($data['groups'] as $group) {
            expect($group)->not->toContain('.');
        }
    });

    it('returns correct level-1 groups', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'level' => 1]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $data = json_decode((string) $response->content(), true);

        expect($data['level'])->toBe(1);
        expect($data['groups'])->toContain('test1.nested');
        expect($data['groups'])->toContain('test2.nested');

        foreach ($data['groups'] as $group) {
            expect(substr_count($group, '.'))->toBe(1);
        }
    });

    it('returns empty groups for level higher than available depth', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'level' => 99]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $data = json_decode((string) $response->content(), true);

        expect($data['total'])->toBe(0);
        expect($data['groups'])->toBe([]);
    });

    it('filters groups by search', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'level' => 0, 'search' => 'test1']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $data = json_decode((string) $response->content(), true);

        expect($data['groups'])->toContain('test1');
        expect($data['groups'])->not->toContain('test2');
    });

    it('search is case-insensitive', function () {
        $lower = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'level' => 0, 'search' => 'test1']));
        $upper = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'level' => 0, 'search' => 'TEST1']));

        $lowerData = json_decode((string) $lower->content(), true);
        $upperData = json_decode((string) $upper->content(), true);

        expect($lowerData['groups'])->toBe($upperData['groups']);
    });

    it('returns empty groups when search matches nothing', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'level' => 0, 'search' => 'no-match-xyz']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $data = json_decode((string) $response->content(), true);

        expect($data['total'])->toBe(0);
        expect($data['groups'])->toBe([]);
    });

    it('returns groups sorted alphabetically', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::PHP, 'level' => 0]));

        $data = json_decode((string) $response->content(), true);

        $sorted = $data['groups'];
        sort($sorted);

        expect($data['groups'])->toBe($sorted);
    });
})->group('Mcp', 'ListTranslationGroupsTool', 'PhpFileHandler');

describe('ListTranslationGroupsTool csv', function () {
    beforeEach(function () {
        $this->tool = new ListTranslationGroupsTool;
        $this->prepareCsvTranslations();
    });

    afterEach(function () {
        $this->cleanCsvTranslations();
    });

    it('returns correct level-0 groups', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::CSV]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $data = json_decode((string) $response->content(), true);

        expect($data['groups'])->toContain('test1');
        expect($data['groups'])->toContain('test2');
    });
})->group('Mcp', 'ListTranslationGroupsTool', 'CsvFileHandler');

describe('ListTranslationGroupsTool database', function () {
    beforeEach(function () {
        $this->tool = new ListTranslationGroupsTool;
        $this->prepareDbTranslations();
    });

    it('returns correct level-0 groups', function () {
        $response = $this->tool->handle(new Request(['format' => TranslationOptions::DB]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $data = json_decode((string) $response->content(), true);

        expect($data['groups'])->toContain('test1');
        expect($data['groups'])->toContain('test2');
    });
})->group('Mcp', 'ListTranslationGroupsTool', 'DatabaseHandler');

describe('ListTranslationGroupsTool errors', function () {
    beforeEach(function () {
        $this->tool = new ListTranslationGroupsTool;
    });

    it('returns an error response for invalid format', function () {
        $response = $this->tool->handle(new Request(['format' => 'invalid_format']));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });
})->group('Mcp', 'ListTranslationGroupsTool');
