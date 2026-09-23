<?php

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\JsonFileHandler;
use BrunosCode\TranslationHandler\TranslationHandlerService;
use Illuminate\Support\Facades\File;

/**
 * Shared files (JSON per locale, a single CSV) also hold entries the handler
 * does not manage: other groups, plain sentence keys, extra locale columns.
 * A write must replace only the managed part and leave the rest untouched.
 *
 * The facade is partially mocked in Pest.php with default options, so the
 * service-level cases use a fresh instance with narrowed fileNames/locales.
 */
function scopedService(array $overrides): TranslationHandlerService
{
    return (new TranslationHandlerService)->setOptions(new TranslationOptions(test()->config($overrides)));
}

function jsonPath(string $locale): string
{
    return lang_path("json-test/{$locale}/test-translations.json");
}

function csvPath(): string
{
    return storage_path('lang/csv-test/test-translations.csv');
}

describe('unmanaged JSON entries', function () {
    beforeEach(function () {
        File::ensureDirectoryExists(dirname(jsonPath('en')));
        File::put(jsonPath('en'), json_encode([
            'Welcome to the site.' => 'Welcome to the site.',
            'other.key' => 'other',
            'test1.get' => 'get-1-en',
            'test2.get' => 'get-2-en',
        ]));
    });

    afterEach(fn () => File::deleteDirectory(lang_path('json-test')));

    it('keeps keys of unmanaged groups and sentence keys on write', function () {
        $service = scopedService(['fileNames' => ['test1'], 'locales' => ['en']]);

        $service->set(new TranslationCollection([new Translation('test1.new', 'en', 'x')]), TranslationOptions::JSON);

        expect(json_decode(File::get(jsonPath('en')), true))->toBe([
            'test1.get' => 'get-1-en',
            'test1.new' => 'x',
            'Welcome to the site.' => 'Welcome to the site.',
            'other.key' => 'other',
            'test2.get' => 'get-2-en',
        ]);
    });

    it('removes a managed key that is no longer in the collection but keeps the rest', function () {
        $service = scopedService(['fileNames' => ['test1'], 'locales' => ['en']]);

        expect($service->deleteKey(TranslationOptions::JSON, 'test1.get'))->toBe(1);

        expect(json_decode(File::get(jsonPath('en')), true))->toBe([
            'Welcome to the site.' => 'Welcome to the site.',
            'other.key' => 'other',
            'test2.get' => 'get-2-en',
        ]);
    });

    it('leaves a file with only unmanaged keys untouched when nothing is written', function () {
        File::put(jsonPath('en'), $before = json_encode(['Hello' => 'Hello']));
        $handler = new JsonFileHandler(new TranslationOptions($this->config(['locales' => ['en']])));

        expect($handler->put(new TranslationCollection))->toBe(0);
        expect(File::get(jsonPath('en')))->toBe($before);
    });

    it('deletes the file when the last managed key goes and nothing else is in it', function () {
        File::put(jsonPath('en'), json_encode(['test1.get' => 'g']));
        $handler = new JsonFileHandler(new TranslationOptions($this->config(['locales' => ['en']])));

        expect($handler->put(new TranslationCollection))->toBe(1);
        expect(File::exists(jsonPath('en')))->toBeFalse();
    });

    it('keeps unmanaged top-level groups in a nested file', function () {
        File::put(jsonPath('en'), json_encode(['other' => ['key' => 'o'], 'test1' => ['get' => 'g']]));
        $handler = new JsonFileHandler(new TranslationOptions($this->config(['jsonNested' => true, 'locales' => ['en']])));

        $handler->put(new TranslationCollection([new Translation('test1.new', 'en', 'x')]));

        expect(json_decode(File::get(jsonPath('en')), true))->toBe([
            'test1' => ['new' => 'x'],
            'other' => ['key' => 'o'],
        ]);
    });
})->group('JsonFileHandler', 'UnmanagedEntries');

describe('unmanaged CSV entries', function () {
    beforeEach(function () {
        File::ensureDirectoryExists(dirname(csvPath()));
        File::put(csvPath(), "key;en;it;de\nother.key;o-en;o-it;o-de\ntest1.get;g1-en;g1-it;g1-de\ntest2.get;g2-en;g2-it;g2-de\n");
    });

    afterEach(fn () => File::deleteDirectory(storage_path('lang/csv-test')));

    it('keeps rows of unmanaged groups and columns of unconfigured locales', function () {
        $service = scopedService(['fileNames' => ['test1'], 'locales' => ['en']]);

        $service->set(new TranslationCollection([
            new Translation('test1.get', 'en', 'changed'),
            new Translation('test1.new', 'en', 'x'),
        ]), TranslationOptions::CSV, force: true);

        expect(File::get(csvPath()))->toBe(
            "key;en;it;de\n"
            ."test1.get;changed;g1-it;g1-de\n"
            ."test1.new;x;;\n"
            ."other.key;o-en;o-it;o-de\n"
            ."test2.get;g2-en;g2-it;g2-de\n"
        );
    });

    it('appends a column for a newly configured locale without losing existing ones', function () {
        $service = scopedService(['fileNames' => ['test1'], 'locales' => ['fr']]);

        $service->set(new TranslationCollection([new Translation('test1.get', 'fr', 'g1-fr')]), TranslationOptions::CSV);

        expect(File::get(csvPath()))->toContain("key;en;it;de;fr\n")
            ->toContain("test1.get;g1-en;g1-it;g1-de;g1-fr\n")
            ->toContain("other.key;o-en;o-it;o-de;\n");
    });
})->group('CsvFileHandler', 'UnmanagedEntries');
