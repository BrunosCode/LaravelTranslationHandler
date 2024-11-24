<?php

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $options = new TranslationOptions;
    $csvHandler = app($options->csvHandlerClass, [$options]);

    TranslationHandler::shouldReceive('getOptions')->andReturn($options);
    TranslationHandler::shouldReceive('getCsvHandler')->andReturn($csvHandler);

    if (! File::exists($options->csvPath)) {
        File::makeDirectory($options->csvPath, 0777, true);
    }

    File::put(
        "{$options->csvPath}/{$options->csvFileName}.csv",
        "key;en;it\ntest1.get;get-1-en;get-1-it\ntest2.get;get-2-en;get-2-it"
    );
});

afterEach(function () {
    $options = TranslationHandler::getOptions();
    File::delete("{$options->csvPath}/{$options->csvFileName}.csv");
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
