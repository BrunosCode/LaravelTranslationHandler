<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Mcp\Tools\SetTranslationGroupTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

uses(RefreshDatabase::class);

describe('SetTranslationGroupTool php', function () {
    beforeEach(function () {
        $this->tool = new SetTranslationGroupTool;
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('writes every subkey in the group across locales', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'group' => 'test1',
            'translations' => [
                'welcome' => ['en' => 'Welcome', 'it' => 'Benvenuto'],
                'logout' => ['en' => 'Logout', 'it' => 'Esci'],
            ],
        ]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $collection = TranslationHandler::get(TranslationOptions::PHP);
        expect($collection->whereKey('test1.welcome')->whereLocale('en')->first()?->value)->toBe('Welcome');
        expect($collection->whereKey('test1.welcome')->whereLocale('it')->first()?->value)->toBe('Benvenuto');
        expect($collection->whereKey('test1.logout')->whereLocale('en')->first()?->value)->toBe('Logout');
        expect($collection->whereKey('test1.logout')->whereLocale('it')->first()?->value)->toBe('Esci');
    });

    it('does not overwrite existing translations without force', function () {
        $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'group' => 'test1',
            'translations' => [
                'get' => ['en' => 'Overwritten'],
            ],
        ]));

        expect(
            TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.get')->whereLocale('en')->first()?->value
        )->toBe('get-1-en');
    });

    it('overwrites existing translations with force', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'group' => 'test1',
            'translations' => [
                'get' => ['en' => 'Overwritten EN', 'it' => 'Soprascritto IT'],
            ],
            'force' => true,
        ]));

        expect($response->isError())->toBeFalse();

        $collection = TranslationHandler::get(TranslationOptions::PHP);
        expect($collection->whereKey('test1.get')->whereLocale('en')->first()?->value)->toBe('Overwritten EN');
        expect($collection->whereKey('test1.get')->whereLocale('it')->first()?->value)->toBe('Soprascritto IT');
    });

    it('accepts trailing delimiter on group', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'group' => 'test1.',
            'translations' => [
                'newkey' => ['en' => 'New EN'],
            ],
        ]));

        expect($response->isError())->toBeFalse();

        expect(
            TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.newkey')->whereLocale('en')->first()?->value
        )->toBe('New EN');
    });

    it('supports nested subkeys (containing the delimiter)', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'group' => 'test1',
            'translations' => [
                'nested.deep' => ['en' => 'Deep EN'],
            ],
        ]));

        expect($response->isError())->toBeFalse();

        expect(
            TranslationHandler::get(TranslationOptions::PHP)->whereKey('test1.nested.deep')->whereLocale('en')->first()?->value
        )->toBe('Deep EN');
    });
})->group('Mcp', 'SetTranslationGroupTool', 'PhpFileHandler');

describe('SetTranslationGroupTool database', function () {
    beforeEach(function () {
        $this->tool = new SetTranslationGroupTool;
        $this->prepareDbTranslations();
    });

    it('writes every subkey in the group across locales', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::DB,
            'group' => 'test1',
            'translations' => [
                'welcome' => ['en' => 'Welcome', 'it' => 'Benvenuto'],
                'logout' => ['en' => 'Logout', 'it' => 'Esci'],
            ],
        ]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();

        $collection = TranslationHandler::get(TranslationOptions::DB);
        expect($collection->whereKey('test1.welcome')->whereLocale('en')->first()?->value)->toBe('Welcome');
        expect($collection->whereKey('test1.logout')->whereLocale('it')->first()?->value)->toBe('Esci');
    });
})->group('Mcp', 'SetTranslationGroupTool', 'DatabaseHandler');

describe('SetTranslationGroupTool errors', function () {
    beforeEach(function () {
        $this->tool = new SetTranslationGroupTool;
    });

    it('returns an error for invalid format', function () {
        $response = $this->tool->handle(new Request([
            'format' => 'invalid_format',
            'group' => 'test1',
            'translations' => ['welcome' => ['en' => 'Welcome']],
        ]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });

    it('returns an error for empty group', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'group' => '',
            'translations' => ['welcome' => ['en' => 'Welcome']],
        ]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });

    it('returns an error for empty translations', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'group' => 'test1',
            'translations' => [],
        ]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });

    it('returns an error when a subkey has no locale values', function () {
        $response = $this->tool->handle(new Request([
            'format' => TranslationOptions::PHP,
            'group' => 'test1',
            'translations' => [
                'welcome' => [],
            ],
        ]));

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeTrue();
    });
})->group('Mcp', 'SetTranslationGroupTool');
