<?php

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $options = new TranslationOptions;
    $jsonHandler = app($options->jsonHandlerClass, [$options]);

    TranslationHandler::shouldReceive('getOptions')->andReturn($options);
    TranslationHandler::shouldReceive('getJsonHandler')->andReturn($jsonHandler);

    if (! File::exists($options->jsonPath)) {
        File::makeDirectory($options->jsonPath, 0777, true);
    }

    foreach ($options->locales as $locale) {
        File::put(
            "{$options->jsonPath}/{$locale}.json",
            json_encode([
                'test1.get' => "get-1-{$locale}",
                'test2.get' => "get-2-{$locale}",
            ])
        );
    }
});

afterEach(function () {
    $options = TranslationHandler::getOptions();
    foreach ($options->locales as $locale) {
        File::delete("{$options->jsonPath}/{$locale}.json");
    }
});

describe('JsonFileHandler get', function () {
    test('get method throws an exception for an empty path', function () {
        $jsonHandler = TranslationHandler::getJsonHandler();

        expect(fn () => $jsonHandler->get(path: ''))
            ->toThrow(InvalidArgumentException::class);
    });

    test('get method reads translations from a JSON file', function () {
        $translations = TranslationHandler::getJsonHandler()->get();

        expect($translations)->toBeInstanceOf(TranslationCollection::class);
        expect($translations)->toHaveCount(4);

        $firstTranslation = $translations->first();
        expect($firstTranslation)->toBeInstanceOf(Translation::class);
        expect($firstTranslation->key)->toBe('test1.get');
        expect($firstTranslation->locale)->toBe('en');
        expect($firstTranslation->value)->toBe('get-1-en');
    });
})->group('JsonFileHandler');

describe('JsonFileHandler put', function () {

    test('put method throws an exception for an empty path', function () {
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

    test('put method writes translations to a JSON file', function () {
        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', 'put-1-en'),
            new Translation('test1.put', 'it', 'put-2-it'),
            new Translation('test2.put', 'en', 'put-1-en'),
            new Translation('test2.put', 'it', 'put-2-it'),
        ]);

        $count = TranslationHandler::getJsonHandler()->put($translations);

        expect($count)->toBe(4);
    });
})->group('JsonFileHandler');

describe('JsonFileHandler delete', function () {
    test('delete method throws an exception for an empty path', function () {
        $jsonHandler = TranslationHandler::getJsonHandler();

        expect(fn () => $jsonHandler->delete(path: ''))
            ->toThrow(InvalidArgumentException::class);
    });

    test('delete method deletes the JSON file', function () {
        $result = TranslationHandler::getJsonHandler()->delete();

        expect($result)->toBe(4);
    });
})->group('JsonFileHandler');
