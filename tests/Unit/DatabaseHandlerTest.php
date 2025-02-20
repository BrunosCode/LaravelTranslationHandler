<?php

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\Interfaces\DatabaseHandlerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->prepareDbTranslations();
});

describe('DatabaseHandler common', function () {
    it('can get handler', function () {
        $handler = TranslationHandler::getDbHandler();

        expect($handler)->toBeInstanceOf(DatabaseHandlerInterface::class);
    });
})->group('DatabaseHandler');

describe('DatabaseHandler get', function () {
    it('can get translations from database', function () {
        $handler = TranslationHandler::getDbHandler();

        $translations = $handler->get();

        expect($translations)->toBeInstanceOf(TranslationCollection::class);
        expect($translations->count())->toBe(4);
        expect(DB::table('translation_keys')->count())->toBe(2);
        expect(DB::table('translation_values')->count())->toBe(4);
    });
})->group('DatabaseHandler');

describe('DatabaseHandler put', function () {
    it('can put translations in database', function () {
        $handler = TranslationHandler::getDbHandler();

        $this->cleanDbTranslations();
        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', 'put-1-en'),
            new Translation('test2.put', 'it', 'put-2-it'),
        ]);

        expect($handler->put($translations))->toBe(2);
        expect(DB::table('translation_keys')->count())->toBe(2);
        expect(DB::table('translation_values')->count())->toBe(2);
    });

    it('can put translations in database with empty values', function () {
        $handler = TranslationHandler::getDbHandler();

        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', ''),
        ]);

        expect($handler->put($translations))->toBe(1);
        expect(DB::table('translation_keys')->count())->toBe(3);
        expect(DB::table('translation_values')->count())->toBe(5);
        expect(
            DB::table('translation_values as tv')
                ->join('translation_keys as tk', 'tk.id', '=', 'tv.translation_key_id')
                ->where('tk.key', 'test1.put')
                ->where('tv.locale', 'en')
                ->whereNull('tk.deleted_at')
                ->first()
                ?->value
        )->toBe('');
    });

    it('can put translations in database and update old values', function () {
        $handler = TranslationHandler::getDbHandler();

        $oldTranslationValue = DB::table('translation_values')
            ->whereNull('deleted_at')
            ->where('key', 'test1.get')
            ->where('locale', 'en')
            ->first()
            ?->value;
        $newTranslationValue = 'new-value';

        $translations = new TranslationCollection([
            new Translation('test1.get', 'en', $newTranslationValue),
        ]);

        expect($handler->put($translations))->toBe(1);
        expect(
            DB::table('translation_values as tv')
                ->join('translation_keys as tk', 'tk.id', '=', 'tv.translation_key_id')
                ->whereNull('tk.deleted_at')
                ->where('tk.key', 'test1.get')
                ->where('tv.locale', 'en')
                ->first()
                ?->value
        )->not()->toBe($oldTranslationValue);
        expect(
            DB::table('translation_values as tv')
                ->join('translation_keys as tk', 'tk.id', '=', 'tv.translation_key_id')
                ->whereNull('tv.deleted_at')
                ->where('tk.key', 'test1.get')
                ->where('tv.locale', 'en')
                ->first()
                ?->value
        )->toBe($newTranslationValue);
    });

    it('can put translations in database and soft delete all old key and values', function () {
        $handler = TranslationHandler::getDbHandler();

        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', 'put-1-en'),
        ]);

        expect($handler->put($translations))->toBe(1);
        expect(DB::table('translation_keys')->whereNull('deleted_at')->count())->toBe(1);
        expect(DB::table('translation_keys')->count())->toBe(3);
        expect(DB::table('translation_values')->whereNull('deleted_at')->count())->toBe(1);
        expect(DB::table('translation_values')->count())->toBe(5);
    });

    it('can update value and restore key', function () {
        DB::table('translation_keys')->update(['deleted_at' => now()]);
        DB::table('translation_values')->update(['deleted_at' => now()]);

        $handler = TranslationHandler::getDbHandler();

        $restoredKey = 'test1.get';

        $translations = new TranslationCollection([
            new Translation($restoredKey, 'en', 'new-value'),
        ]);

        expect($handler->put($translations))->toBe(1);

        // the restored key is active
        expect(
            DB::table('translation_keys as tk')
                ->where('tk.key', $restoredKey)
                ->whereNull('tk.deleted_at')
                ->count()
        )->toBe(1);
        // the restored key has 2 active values
        expect(
            DB::table('translation_values as tv')
                ->join('translation_keys as tk', 'tk.id', '=', 'tv.translation_key_id')
                ->where('tk.key', $restoredKey)
                ->whereNull('tv.deleted_at')
                ->count()
        )->toBe(1);
        // the restored key has the new value
        expect(
            DB::table('translation_values as tv')
                ->join('translation_keys as tk', 'tk.id', '=', 'tv.translation_key_id')
                ->where('tk.key', $restoredKey)
                ->where('tv.locale', 'en')
                ->whereNull('tv.deleted_at')
                ->first()
                ?->value
        )->toBe('new-value');
    });
})->group('DatabaseHandler');

describe('DatabaseHandler delete', function () {
    it('can delete translations in database', function () {
        $handler = TranslationHandler::getDbHandler();

        expect($handler->delete())->toBe(4);
        expect(DB::table('translation_keys')->whereNull('deleted_at')->count())->toBe(0);
        expect(DB::table('translation_values')->whereNull('deleted_at')->count())->toBe(0);
        expect(DB::table('translation_keys')->count())->toBe(2);
        expect(DB::table('translation_values')->count())->toBe(4);
    });

    it('can delete translations in database with hard delete', function () {
        DB::table('translation_keys')->update(['deleted_at' => now()]);

        $handler = TranslationHandler::getDbHandler();

        expect($handler->delete(hardDelete: true))->toBe(4);
        expect(DB::table('translation_keys')->count())->toBe(0);
        expect(DB::table('translation_values')->count())->toBe(0);
    });
})->group('DatabaseHandler');
