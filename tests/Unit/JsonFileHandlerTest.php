<?php

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Support\Facades\File;

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

    it('coerces numbers and booleans and keeps null values', function () {
        File::put(lang_path('json-test/en/test-translations.json'), json_encode([
            'test1.count' => 5,
            'test1.on' => true,
            'test1.none' => null,
        ]));

        $translations = TranslationHandler::getJsonHandler()->get()->whereLocale('en');

        expect($translations->whereKey('test1.count')->first()?->value)->toBe('5');
        expect($translations->whereKey('test1.on')->first()?->value)->toBe('true');
        expect($translations->whereKey('test1.none')->first()?->value)->toBeNull();
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

        // Per locale: 4 pre-existing leaves removed + 2 new added = 6; × 2 locales = 12
        expect($count)->toBe(12);
    });

    it('writes translations to a nested JSON file', function () {
        $translations = new TranslationCollection([
            new Translation('test1.nested.put', 'en', 'put-1-en'),
            new Translation('test1.nested.put', 'it', 'put-2-it'),
            new Translation('test2.nested.put', 'en', 'put-1-en'),
            new Translation('test2.nested.put', 'it', 'put-2-it'),
        ]);

        $count = TranslationHandler::getJsonHandler()->put($translations);

        // Per locale: 4 pre-existing leaves removed + 2 new added = 6; × 2 locales = 12
        expect($count)->toBe(12);
    });

    it('returns 0 when content is unchanged', function () {
        $existing = TranslationHandler::getJsonHandler()->get();

        $count = TranslationHandler::getJsonHandler()->put($existing);

        expect($count)->toBe(0);
    });

    it('refuses invalid UTF-8 and leaves the existing file intact', function () {
        $path = lang_path('json-test/en/test-translations.json');
        $before = File::get($path);

        $translations = TranslationHandler::getJsonHandler()->get()
            ->addTranslation(new Translation('test1.bad', 'en', "caf\xE9"));

        expect(fn () => TranslationHandler::getJsonHandler()->put($translations))
            ->toThrow(JsonException::class);

        expect(File::get($path))->toBe($before);
    });

    it('writes unicode and slashes unescaped', function () {
        $translations = TranslationHandler::getJsonHandler()->get()
            ->addTranslation(new Translation('test1.accent', 'en', 'caffè / tè'));

        TranslationHandler::getJsonHandler()->put($translations);

        expect(File::get(lang_path('json-test/en/test-translations.json')))->toContain('"caffè / tè"');
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
