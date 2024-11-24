<?php

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Interfaces\DatabaseHandlerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function insertGetTranslations(): void
{
    DB::table('translation_keys')->insert([
        [
            'key' => 'test1.get',
            'updated_at' => now(),
            'created_at' => now(),
        ],
        [
            'key' => 'test2.get',
            'updated_at' => now(),
            'created_at' => now(),
        ],
    ]);

    DB::table('translation_values')->insert([
        [
            'translation_key_id' => 1,
            'locale' => 'en',
            'value' => 'value1',
            'updated_at' => now(),
            'created_at' => now(),
        ],
        [
            'translation_key_id' => 2,
            'locale' => 'it',
            'value' => 'value2',
            'updated_at' => now(),
            'created_at' => now(),
        ],
    ]);
}

beforeEach(function () {
    $options = new TranslationOptions;
    $dbHandler = app($options->dbHandlerClass, [$options]);

    TranslationHandler::shouldReceive('getOptions')->andReturn($options);
    TranslationHandler::shouldReceive('getDbHandler')->andReturn($dbHandler);
});

describe('DatabaseHandler common', function () {
    it('can get handler', function () {
        $handler = TranslationHandler::getDbHandler();

        expect($handler)->toBeInstanceOf(DatabaseHandlerInterface::class);
    });
})->group('DatabaseHandler');

describe('DatabaseHandler get', function () {
    it('can get translations from database', function () {
        insertGetTranslations();

        $handler = TranslationHandler::getDbHandler();

        $translations = $handler->get();

        expect($translations)->toBeInstanceOf(TranslationCollection::class);
        expect($translations->count())->toBe(2);
        expect(DB::table('translation_keys')->count())->toBe(2);
        expect(DB::table('translation_values')->count())->toBe(2);
    });
})->group('DatabaseHandler');

describe('DatabaseHandler put', function () {
    it('can put translations in database', function () {
        $handler = TranslationHandler::getDbHandler();

        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', 'value1'),
            new Translation('test2.put', 'it', 'value2'),
        ]);

        expect($handler->put($translations))->toBe(2);
        expect(DB::table('translation_keys')->count())->toBe(2);
        expect(DB::table('translation_values')->count())->toBe(2);
    });

    it('can put translations in database and soft delete old values', function () {
        insertGetTranslations();

        $handler = TranslationHandler::getDbHandler();

        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', 'value1'),
            new Translation('test2.put', 'it', 'value2'),
        ]);

        expect($handler->put($translations))->toBe(2);
        expect(DB::table('translation_keys')->whereNotNull('deleted_at')->count())->toBe(2);
        expect(DB::table('translation_values')->whereNotNull('deleted_at')->count())->toBe(2);
    });

    it('can put translations in database with empty values', function () {
        $handler = TranslationHandler::getDbHandler();

        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', ''),
            new Translation('test2.put', 'it', ''),
        ]);

        expect($handler->put($translations))->toBe(2);
        expect(DB::table('translation_keys')->count())->toBe(2);
        expect(DB::table('translation_values')->count())->toBe(2);
    });

    it('can put translations in database and update old values', function () {
        insertGetTranslations();

        $handler = TranslationHandler::getDbHandler();

        $translations = new TranslationCollection([
            new Translation('test1.get', 'en', 'value1'),
            new Translation('test2.get', 'it', 'value2'),
            new Translation('test1.put', 'en', 'value1'),
            new Translation('test2.put', 'it', 'value2'),
        ]);

        expect($handler->put($translations))->toBe(4);
        expect(DB::table('translation_keys')->count())->toBe(4);
        expect(DB::table('translation_values')->count())->toBe(4);
    });

    it('can put translations in database and restore soft deleted values', function () {
        insertGetTranslations();
        DB::table('translation_keys')->update(['deleted_at' => now()]);

        $handler = TranslationHandler::getDbHandler();

        $translations = new TranslationCollection([
            new Translation('test1.get', 'en', 'value1'),
            new Translation('test2.get', 'it', 'value2'),
            new Translation('test1.put', 'en', 'value1'),
            new Translation('test2.put', 'it', 'value2'),
        ]);

        expect($handler->put($translations))->toBe(4);
        expect(DB::table('translation_keys')->count())->toBe(4);
        expect(DB::table('translation_values')->count())->toBe(4);
    });

})->group('DatabaseHandler');

describe('DatabaseHandler delete', function () {
    it('can delete translations in database', function () {
        insertGetTranslations();

        $handler = TranslationHandler::getDbHandler();

        expect($handler->delete())->toBe(2);
        expect(DB::table('translation_keys')->whereNull('deleted_at')->count())->toBe(0);
        expect(DB::table('translation_values')->whereNull('deleted_at')->count())->toBe(0);
    });

    it('can delete translations in database with hard delete', function () {
        insertGetTranslations();
        DB::table('translation_keys')->update(['deleted_at' => now()]);

        $handler = TranslationHandler::getDbHandler();

        expect($handler->delete(hardDelete: true))->toBe(2);
        expect(DB::table('translation_keys')->count())->toBe(0);
        expect(DB::table('translation_values')->count())->toBe(0);
    });
})->group('DatabaseHandler');
