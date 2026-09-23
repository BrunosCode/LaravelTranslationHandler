<?php

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Guards the query budget of a single-key write against a populated table.
 * Before the fix, one set() against 2000 keys issued ~2000 UPDATE statements
 * (one per kept key in the soft-delete pass).
 */
describe('database write performance', function () {
    beforeEach(function () {
        $keys = [];
        $values = [];

        for ($i = 1; $i <= 500; $i++) {
            $keys[] = ['id' => $i, 'key' => "test1.group{$i}.item", 'created_at' => now(), 'updated_at' => now()];

            foreach (['en', 'it'] as $locale) {
                $values[] = ['translation_key_id' => $i, 'locale' => $locale, 'value' => "v{$i}-{$locale}", 'created_at' => now(), 'updated_at' => now()];
            }
        }

        foreach (array_chunk($keys, 250) as $chunk) {
            DB::table('translation_keys')->insert($chunk);
        }

        foreach (array_chunk($values, 250) as $chunk) {
            DB::table('translation_values')->insert($chunk);
        }
    });

    it('updates a single value with a constant number of queries', function () {
        DB::enableQueryLog();

        $written = TranslationHandler::set(
            new TranslationCollection([new Translation('test1.group5.item', 'en', 'changed')]),
            TranslationOptions::DB,
            force: true,
        );

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($written)->toBe(1);
        expect(DB::table('translation_values')->where('translation_key_id', 5)->where('locale', 'en')->value('value'))->toBe('changed');
        expect(DB::table('translation_values')->whereNull('deleted_at')->count())->toBe(1000);
        expect($queries)->toBeLessThanOrEqual(12);
    });
})->group('DatabaseHandler', 'Performance');
