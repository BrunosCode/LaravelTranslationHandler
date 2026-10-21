<?php

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\CsvFileHandler;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Support\Facades\File;

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

    test('get method skips locales whose column is missing from the header', function () {
        $options = TranslationHandler::getOptions();
        File::put("{$options->csvPath}/{$options->csvFileName}.csv", "key;en\ntest1.get;get-1-en\n");

        $translations = TranslationHandler::getCsvHandler()->get();

        expect($translations)->toHaveCount(1);
        expect($translations->first()->locale)->toBe('en');
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

        // 4 pre-existing cells (test1.get/test2.get × en/it) removed + 4 new added = 8
        expect($count)->toBe(8);
    });

    test('put returns 0 when content is unchanged', function () {
        $existing = TranslationHandler::getCsvHandler()->get();

        $count = TranslationHandler::getCsvHandler()->put($existing);

        expect($count)->toBe(0);
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

    test('values that spreadsheets would run as formulas are escaped on write and restored on read', function () {
        $values = ['=SUM(A1:A2)', '+39 333', '-5%', '@user', "\tindent", "'=already quoted", "'plain quote", 'normal'];

        $translations = new TranslationCollection(array_map(
            fn (string $value, int $i) => new Translation("test1.v{$i}", 'en', $value),
            $values,
            array_keys($values),
        ));

        TranslationHandler::getCsvHandler()->put($translations);

        $raw = File::get(storage_path('lang/csv-test/test-translations.csv'));
        // fputcsv() wraps values containing spaces in double quotes.
        expect($raw)->toContain("test1.v0;'=SUM(A1:A2);")
            ->toContain("test1.v1;\"'+39 333\";")
            ->toContain("test1.v2;'-5%;")
            ->toContain("test1.v3;'@user;")
            ->toContain("test1.v5;\"''=already quoted\";")
            ->toContain("test1.v6;\"'plain quote\";")
            ->toContain('test1.v7;normal;');

        $read = TranslationHandler::getCsvHandler()->get()->whereLocale('en');

        foreach ($values as $i => $value) {
            expect($read->whereKey("test1.v{$i}")->first()?->value)->toBe($value);
        }
    });

    test('formula escaping can be disabled', function () {
        $handler = new CsvFileHandler(new TranslationOptions($this->config(['csvEscapeFormulas' => false])));

        $handler->put(new TranslationCollection([new Translation('test1.f', 'en', '=1+1')]));

        expect(File::get(storage_path('lang/csv-test/test-translations.csv')))->toContain('test1.f;=1+1;');
        expect($handler->get()->whereKey('test1.f')->first()?->value)->toBe('=1+1');
    });

    test('blank lines are ignored when reading', function () {
        $options = TranslationHandler::getOptions();
        File::put("{$options->csvPath}/{$options->csvFileName}.csv", "key;en;it\n\ntest1.get;a;b\n\n");

        expect(TranslationHandler::getCsvHandler()->get())->toHaveCount(2);
    });

    test('an empty file reads as no translations', function () {
        $options = TranslationHandler::getOptions();
        File::put("{$options->csvPath}/{$options->csvFileName}.csv", '');

        expect(TranslationHandler::getCsvHandler()->get())->toHaveCount(0);
    });
})->group('CsvFileHandler');
