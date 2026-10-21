<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * The published stubs must be safe to run when the tables already exist,
 * e.g. created by TwillTranslationHandler's own migrations.
 */
function migrationStub(string $name): object
{
    return require __DIR__."/../../database/migrations/{$name}.php.stub";
}

describe('migration stubs', function () {
    it('create both tables on an empty schema', function () {
        Schema::dropIfExists('translation_values');
        Schema::dropIfExists('translation_keys');

        migrationStub('create_translation_keys_table')->up();
        migrationStub('create_translation_values_table')->up();

        expect(Schema::hasColumns('translation_keys', ['id', 'key', 'created_at', 'updated_at', 'deleted_at']))->toBeTrue();
        expect(Schema::hasColumns('translation_values', ['id', 'translation_key_id', 'locale', 'value', 'created_at', 'updated_at', 'deleted_at']))->toBeTrue();
    });

    it('skip tables that already exist without touching their data or extra columns', function () {
        Schema::dropIfExists('translation_values');
        Schema::dropIfExists('translation_keys');

        // Shape created by TwillTranslationHandler, with its extra column.
        Schema::create('translation_keys', function ($table) {
            $table->id();
            $table->string('key')->unique();
            $table->boolean('published')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('translation_values', function ($table) {
            $table->id();
            $table->foreignId('translation_key_id')->constrained('translation_keys')->onDelete('cascade');
            $table->string('locale', 7);
            $table->text('value')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        $id = DB::table('translation_keys')->insertGetId(['key' => 'test1.kept', 'created_at' => now(), 'updated_at' => now()]);

        migrationStub('create_translation_keys_table')->up();
        migrationStub('create_translation_values_table')->up();

        expect(DB::table('translation_keys')->where('id', $id)->value('key'))->toBe('test1.kept');
        expect(Schema::hasColumn('translation_keys', 'published'))->toBeTrue();
        expect(Schema::hasColumn('translation_values', 'active'))->toBeTrue();
    });

    it('can be run twice', function () {
        migrationStub('create_translation_keys_table')->up();
        migrationStub('create_translation_values_table')->up();
        migrationStub('create_translation_keys_table')->up();
        migrationStub('create_translation_values_table')->up();

        expect(Schema::hasTable('translation_keys'))->toBeTrue();
        expect(Schema::hasTable('translation_values'))->toBeTrue();
    });
})->group('Migrations');
