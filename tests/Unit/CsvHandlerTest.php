<?php

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;

beforeEach(function () {
    $this->prepareCsvTranslations();
});

afterEach(function () {
    $this->cleanCsvTranslations();
});

describe('CsvFileHandler get', function () {
    test('get method throws an exception for an empty path', function () {
        $csvHandler = TranslationHandler::getCsvHandler();

        expect(fn () => $csvHandler->get(path: ''))
            ->toThrow(InvalidArgumentException::class);
    });

    test('get method reads translations from a CSV file', function () {
        $translations = TranslationHandler::getCsvHandler()->get();

        expect($translations)->toBeInstanceOf(TranslationCollection::class);
        expect($translations)->toHaveCount(4);

        $firstTranslation = $translations->first();
        expect($firstTranslation)->toBeInstanceOf(Translation::class);
        expect($firstTranslation->key)->toBe('test1.get');
        expect($firstTranslation->locale)->toBe('en');
        expect($firstTranslation->value)->toBe('get-1-en');
    });
})->group('CsvFileHandler');

describe('CsvFileHandler put', function () {

    test('put method throws an exception for an empty path', function () {
        $csvHandler = TranslationHandler::getCsvHandler();

        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', 'put-1-en'),
            new Translation('test1.put', 'it', 'put-2-it'),
            new Translation('test2.put', 'en', 'put-1-en'),
            new Translation('test2.put', 'it', 'put-2-it'),
        ]);

        expect(fn () => $csvHandler->put(translations: $translations, path: ''))
            ->toThrow(InvalidArgumentException::class);
    });

    test('put method writes translations to a CSV file', function () {
        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', 'put-1-en'),
            new Translation('test1.put', 'it', 'put-2-it'),
            new Translation('test2.put', 'en', 'put-1-en'),
            new Translation('test2.put', 'it', 'put-2-it'),
        ]);

        $count = TranslationHandler::getCsvHandler()->put($translations);

        expect($count)->toBe(4);
    });
})->group('CsvFileHandler');

describe('CsvFileHandler delete', function () {
    test('delete method throws an exception for an empty path', function () {
        $csvHandler = TranslationHandler::getCsvHandler();

        expect(fn () => $csvHandler->delete(path: ''))
            ->toThrow(InvalidArgumentException::class);
    });

    test('delete method deletes the CSV file', function () {
        $result = TranslationHandler::getCsvHandler()->delete();

        expect($result)->toBe(4);
    });
})->group('CsvFileHandler');
