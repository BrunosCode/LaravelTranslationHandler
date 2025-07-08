<?php

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;

beforeEach(function () {
    $this->prepareJsonTranslations();
});

afterEach(function () {
    $this->cleanJsonTranslations();
});

describe('JsonFileHandler get', function () {
    it('throws an exception for an empty path', function () {
        $jsonHandler = TranslationHandler::getJsonHandler();

        expect(fn () => $jsonHandler->get(path: ''))
            ->toThrow(InvalidArgumentException::class);
    });

    it('reads translations from a JSON file', function () {
        $translations = TranslationHandler::getJsonHandler()->get();

        expect($translations)->toBeInstanceOf(TranslationCollection::class);
        expect($translations)->toHaveCount(8);

        $firstTranslation = $translations->where('key', 'test1.get')->where('locale', 'en')->first();
        expect($firstTranslation)->toBeInstanceOf(Translation::class);
        expect($firstTranslation->value)->toBe('get-1-en');
    });

    it('reads translations from a nested JSON file', function () {
        $translations = TranslationHandler::getJsonHandler()->get();

        expect($translations)->toBeInstanceOf(TranslationCollection::class);
        expect($translations)->toHaveCount(8);

        $firstTranslation = $translations->where('key', 'test1.nested.get')->where('locale', 'en')->first();
        expect($firstTranslation)->toBeInstanceOf(Translation::class);
        expect($firstTranslation->value)->toBe('get-1-en');
    });
})->group('JsonFileHandler');

describe('JsonFileHandler put', function () {
    it('throws an exception for an empty path', function () {
        $jsonHandler = TranslationHandler::getJsonHandler();

        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', 'put-1-en'),
            new Translation('test1.put', 'it', 'put-2-it'),
            new Translation('test2.put', 'en', 'put-1-en'),
            new Translation('test2.put', 'it', 'put-2-it'),
        ]);

        expect(fn () => $jsonHandler->put(translations: $translations, path: ''))
            ->toThrow(InvalidArgumentException::class);
    });

    it('writes translations to a JSON file', function () {
        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', 'put-1-en'),
            new Translation('test1.put', 'it', 'put-2-it'),
            new Translation('test2.put', 'en', 'put-1-en'),
            new Translation('test2.put', 'it', 'put-2-it'),
        ]);

        $count = TranslationHandler::getJsonHandler()->put($translations);

        expect($count)->toBe(4);
    });

    it('writes translations to a nested JSON file', function () {
        $translations = new TranslationCollection([
            new Translation('test1.nested.put', 'en', 'put-1-en'),
            new Translation('test1.nested.put', 'it', 'put-2-it'),
            new Translation('test2.nested.put', 'en', 'put-1-en'),
            new Translation('test2.nested.put', 'it', 'put-2-it'),
        ]);

        $count = TranslationHandler::getJsonHandler()->put($translations);

        expect($count)->toBe(4);
    });
})->group('JsonFileHandler');

describe('JsonFileHandler delete', function () {
    it('throws an exception for an empty path', function () {
        $jsonHandler = TranslationHandler::getJsonHandler();

        expect(fn () => $jsonHandler->delete(path: ''))
            ->toThrow(InvalidArgumentException::class);
    });

    it('deletes the JSON file', function () {
        $result = TranslationHandler::getJsonHandler()->delete();

        expect($result)->toBe(8);
    });
})->group('JsonFileHandler');
