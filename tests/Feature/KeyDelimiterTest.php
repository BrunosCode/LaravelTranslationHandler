<?php

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\TranslationHandlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * The facade is partially mocked in Pest.php with handlers built on the default
 * options, so these tests drive a fresh service instance configured with a
 * "::" delimiter and exercise every handler end to end.
 */
function delimiterService(array $overrides = []): TranslationHandlerService
{
    $options = new TranslationOptions(test()->config(array_merge(['keyDelimiter' => '::'], $overrides)));

    return (new TranslationHandlerService)->setOptions($options);
}

describe('keyDelimiter "::"', function () {
    afterEach(function () {
        File::deleteDirectory(lang_path('php-test'));
        File::deleteDirectory(lang_path('json-test'));
        File::deleteDirectory(storage_path('lang/csv-test'));
    });

    it('reads, filters and writes PHP files', function () {
        $service = delimiterService();

        foreach (['en', 'it'] as $locale) {
            File::ensureDirectoryExists(lang_path("php-test/{$locale}"));
            foreach (['test1', 'test2'] as $file) {
                File::put(lang_path("php-test/{$locale}/{$file}.php"), '<?php return '.var_export(['get' => "g-{$locale}", 'nested' => ['get' => "n-{$locale}"]], true).';');
            }
        }

        $all = $service->get(TranslationOptions::PHP);

        expect($all)->toHaveCount(8);
        expect($all->whereKey('test1::nested::get')->whereLocale('it')->first()?->value)->toBe('n-it');
        expect($service->listTranslations(TranslationOptions::PHP, group: 'test1'))->toHaveCount(4);
        expect($service->listGroups(TranslationOptions::PHP, level: 1)->all())->toBe(['test1::nested', 'test2::nested']);

        $written = $service->set(new TranslationCollection([new Translation('test1::new', 'en', 'x')]), TranslationOptions::PHP);

        expect($written)->toBe(1);
        expect(include lang_path('php-test/en/test1.php'))->toBe(['get' => 'g-en', 'nested' => ['get' => 'n-en'], 'new' => 'x']);
    });

    it('reads and writes flat and nested JSON files', function () {
        $flat = delimiterService();
        File::ensureDirectoryExists(lang_path('json-test/en'));
        File::put(lang_path('json-test/en/test-translations.json'), json_encode(['test1::get' => 'g', 'test2::nested::get' => 'n']));

        expect($flat->get(TranslationOptions::JSON))->toHaveCount(2);
        expect($flat->set(new TranslationCollection([new Translation('test1::new', 'en', 'x')]), TranslationOptions::JSON))->toBe(1);
        expect(json_decode(File::get(lang_path('json-test/en/test-translations.json')), true))->toHaveKey('test1::new', 'x');

        $nested = delimiterService(['jsonNested' => true]);
        File::put(lang_path('json-test/en/test-translations.json'), json_encode(['test1' => ['nested' => ['get' => 'n']]]));

        expect($nested->get(TranslationOptions::JSON)->first()?->key)->toBe('test1::nested::get');
        expect($nested->set(new TranslationCollection([new Translation('test1::new', 'en', 'x')]), TranslationOptions::JSON))->toBe(1);
        expect(json_decode(File::get(lang_path('json-test/en/test-translations.json')), true))->toBe(['test1' => ['nested' => ['get' => 'n'], 'new' => 'x']]);
    });

    it('reads and writes CSV files', function () {
        $service = delimiterService();
        File::ensureDirectoryExists(storage_path('lang/csv-test'));
        File::put(storage_path('lang/csv-test/test-translations.csv'), "key;en;it\ntest1::get;g-en;g-it\n");

        expect($service->get(TranslationOptions::CSV))->toHaveCount(2);
        expect($service->set(new TranslationCollection([new Translation('test2::new', 'en', 'x')]), TranslationOptions::CSV))->toBeGreaterThan(0);
        expect(File::get(storage_path('lang/csv-test/test-translations.csv')))->toContain("test2::new;x;\n");
    });

    it('reads and writes the database', function () {
        $service = delimiterService();
        $id = DB::table('translation_keys')->insertGetId(['key' => 'test1::get', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('translation_values')->insert([
            ['translation_key_id' => $id, 'locale' => 'en', 'value' => 'g-en', 'created_at' => now(), 'updated_at' => now()],
            ['translation_key_id' => $id, 'locale' => 'it', 'value' => 'g-it', 'created_at' => now(), 'updated_at' => now()],
        ]);

        expect($service->get(TranslationOptions::DB))->toHaveCount(2);
        expect($service->set(new TranslationCollection([new Translation('test1::new', 'en', 'x')]), TranslationOptions::DB))->toBe(1);
        expect(DB::table('translation_keys')->where('key', 'test1::new')->exists())->toBeTrue();
        expect(DB::table('translation_keys')->whereNull('deleted_at')->count())->toBe(2);
    });
})->group('KeyDelimiter');
