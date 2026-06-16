<?php

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->preparePhpTranslations();
});

afterEach(function () {
    $this->cleanPhpTranslations();
});

describe('PhpFileHandler get', function () {
    test('get should throw exception when path is empty', function () {
        expect(fn () => TranslationHandler::getPhpHandler()->get(path: ''))->toThrow(InvalidArgumentException::class);
    });

    test('get should return a TranslationCollection with all translations from all files', function () {
        $translations = TranslationHandler::getPhpHandler()->get();

        expect($translations)->toBeInstanceOf(TranslationCollection::class);
        expect($translations->count())->toBe(8);
    });
})->group('PhpFileHandler');

describe('PhpFileHandler put', function () {
    test('put should throw exception when path is empty', function () {
        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', 'put1'),
            new Translation('test2.put', 'it', 'put2'),
        ]);

        $phpHandler = TranslationHandler::getPhpHandler();

        expect(fn () => $phpHandler->put(translations: $translations, path: ''))
            ->toThrow(InvalidArgumentException::class);
    });

    test('put should return the number of translations changed', function () {
        $options = TranslationHandler::getOptions();

        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', 'put1'),
            new Translation('test2.put', 'it', 'put2'),
        ]);

        $result = TranslationHandler::getPhpHandler()->put(translations: $translations);
        $test1 = include "{$options->phpPath}/en/test1.php";
        $test2 = include "{$options->phpPath}/it/test2.php";

        // Pre-existing 8 translations are removed (en/it × test1/test2 × {get, nested.get})
        // and 2 new are added (test1.put/en, test2.put/it) = 10 changes
        expect($result)->toBe(10);
        expect(File::exists("{$options->phpPath}/en/test1.php"))->toBeTrue();
        expect($test1['put'])->toBe('put1');
        expect(File::exists("{$options->phpPath}/it/test2.php"))->toBeTrue();
        expect($test2['put'])->toBe('put2');
    });

    test('put returns 0 when content is unchanged', function () {
        $existing = TranslationHandler::getPhpHandler()->get();

        $result = TranslationHandler::getPhpHandler()->put(translations: $existing);

        expect($result)->toBe(0);
    });

    test('put formats written files with Pint when phpPint is enabled', function () {
        TranslationHandler::setOption('phpPint', true);

        $options = TranslationHandler::getOptions();

        $translations = new TranslationCollection([
            new Translation('test1.put', 'en', 'put1'),
        ]);

        TranslationHandler::getPhpHandler()->put(translations: $translations);

        $content = File::get("{$options->phpPath}/en/test1.php");

        // Pint rewrites the raw var_export long-array syntax to short arrays and
        // appends a trailing newline, while the data round-trips unchanged.
        expect($content)->not->toContain('array (');
        expect($content)->toEndWith("\n");
        expect(include "{$options->phpPath}/en/test1.php")->toBe(['put' => 'put1']);
    });
})->group('PhpFileHandler');

describe('PhpFileHandler delete', function () {
    it('delete should throw exception when path is empty', function () {
        TranslationHandler::getPhpHandler()->delete(path: '');
    })->throws(InvalidArgumentException::class, 'PHP handler path cannot be an empty string');

    test('delete should return the number of translations deleted', function () {
        $options = TranslationHandler::getOptions();
        $result = TranslationHandler::getPhpHandler()->delete();

        expect($result)->toBe(8);
        foreach ($options->locales as $locale) {
            foreach ($options->fileNames as $filename) {
                expect(File::exists("{$options->phpPath}/{$locale}/{$filename}.php"))->toBeFalse();
            }
        }
    });
})->group('PhpFileHandler');
