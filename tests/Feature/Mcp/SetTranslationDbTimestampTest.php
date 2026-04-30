<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Mcp\Tools\SetAllLocalesTranslationTool;
use BrunosCode\TranslationHandler\Mcp\Tools\SetTranslationTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;

uses(RefreshDatabase::class);

/**
 * Regression: writing a single translation via the MCP tools must not bump
 * updated_at on every existing row in the DB. The DB handler's set() flow
 * goes through merge-with-existing → put(full collection), so handleUpdate
 * historically upserted every row with updated_at = now(). This test verifies
 * only the rows that actually changed have their updated_at refreshed.
 */
describe('MCP DB single-write timestamp isolation', function () {
    beforeEach(function () {
        $this->prepareDbTranslations();
    });

    it('does not change updated_at of other keys when set-translation writes a single row', function () {
        $past = Carbon::parse('2020-01-01 00:00:00');

        DB::table('translation_keys')->update(['updated_at' => $past, 'created_at' => $past]);
        DB::table('translation_values')->update(['updated_at' => $past, 'created_at' => $past]);

        $tool = new SetTranslationTool;
        $response = $tool->handle(new Request([
            'format' => TranslationOptions::DB,
            'key' => 'test1.get',
            'locale' => 'en',
            'value' => 'changed',
            'force' => true,
        ]));

        expect($response->isError())->toBeFalse();

        $changedRow = DB::table('translation_values as tv')
            ->join('translation_keys as tk', 'tk.id', '=', 'tv.translation_key_id')
            ->where('tk.key', 'test1.get')
            ->where('tv.locale', 'en')
            ->select('tv.value', 'tv.updated_at')
            ->first();

        expect($changedRow->value)->toBe('changed');
        expect((string) $changedRow->updated_at)->not->toBe((string) $past);

        $untouchedValueRows = DB::table('translation_values as tv')
            ->join('translation_keys as tk', 'tk.id', '=', 'tv.translation_key_id')
            ->where(function ($q) {
                $q->where('tk.key', '!=', 'test1.get')
                    ->orWhere('tv.locale', '!=', 'en');
            })
            ->select('tv.updated_at')
            ->get();

        expect($untouchedValueRows)->not->toBeEmpty();

        foreach ($untouchedValueRows as $row) {
            expect((string) $row->updated_at)->toBe((string) $past);
        }

        $untouchedKeyRows = DB::table('translation_keys')
            ->where('key', '!=', 'test1.get')
            ->select('updated_at')
            ->get();

        foreach ($untouchedKeyRows as $row) {
            expect((string) $row->updated_at)->toBe((string) $past);
        }
    });

    it('does not change updated_at when the new value equals the existing value', function () {
        $past = Carbon::parse('2020-01-01 00:00:00');

        DB::table('translation_keys')->update(['updated_at' => $past]);
        DB::table('translation_values')->update(['updated_at' => $past]);

        $tool = new SetTranslationTool;
        $response = $tool->handle(new Request([
            'format' => TranslationOptions::DB,
            'key' => 'test1.get',
            'locale' => 'en',
            'value' => 'get-1-en',
            'force' => true,
        ]));

        expect($response->isError())->toBeFalse();

        $allValueRows = DB::table('translation_values')->select('updated_at')->get();
        foreach ($allValueRows as $row) {
            expect((string) $row->updated_at)->toBe((string) $past);
        }

        $allKeyRows = DB::table('translation_keys')->select('updated_at')->get();
        foreach ($allKeyRows as $row) {
            expect((string) $row->updated_at)->toBe((string) $past);
        }
    });

    it('does not change updated_at of unrelated keys when set-all-locales writes one key', function () {
        $past = Carbon::parse('2020-01-01 00:00:00');

        DB::table('translation_keys')->update(['updated_at' => $past, 'created_at' => $past]);
        DB::table('translation_values')->update(['updated_at' => $past, 'created_at' => $past]);

        $tool = new SetAllLocalesTranslationTool;
        $response = $tool->handle(new Request([
            'format' => TranslationOptions::DB,
            'key' => 'test1.get',
            'values' => ['en' => 'new-en', 'it' => 'new-it'],
            'force' => true,
        ]));

        expect($response->isError())->toBeFalse();

        $unrelatedRows = DB::table('translation_values as tv')
            ->join('translation_keys as tk', 'tk.id', '=', 'tv.translation_key_id')
            ->where('tk.key', '!=', 'test1.get')
            ->select('tv.updated_at')
            ->get();

        expect($unrelatedRows)->not->toBeEmpty();

        foreach ($unrelatedRows as $row) {
            expect((string) $row->updated_at)->toBe((string) $past);
        }
    });
})->group('Mcp', 'DatabaseHandler', 'TimestampIsolation');
