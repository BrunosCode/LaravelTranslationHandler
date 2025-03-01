<?php

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;

describe('TranslationCollection', function () {
    it('can clone the collection', function () {
        $collection = new TranslationCollection([
            new Translation('key1', 'en', 'Hello'),
            new Translation('key2', 'en', 'World'),
        ]);

        $clonedCollection = $collection->clone();

        expect($clonedCollection)->not()->toBe($collection);
        expect($clonedCollection->count())->toBe(2);
    });

    it('can add a translation', function () {
        $collection = new TranslationCollection();
        $translation = new Translation('key1', 'en', 'Hello');

        $collection = $collection->addTranslation($translation);

        expect($collection->count())->toBe(1);
        expect($collection->first())->toBe($translation);
    });

    it('cannot add a duplicate translation', function () {
        $collection = new TranslationCollection([
            new Translation('key1', 'en', 'Hello'),
        ]);
        $translation = new Translation('key1', 'en', 'Hello');

        $collection = $collection->addTranslation($translation);

        expect($collection->count())->toBe(1);
    });

    it('can replace a translation', function () {
        $collection = new TranslationCollection([
            new Translation('key1', 'en', 'Hello'),
        ]);
        $translation = new Translation('key1', 'en', 'World');

        $collection = $collection->replaceTranslation($translation);

        expect($collection->count())->toBe(1);
        expect($collection->first())->toBe($translation);
    });

    it('can add multiple translations', function () {
        $existing = new TranslationCollection([
            new Translation('key1', 'en', 'Hello'),
        ]);

        $newTranslations = new TranslationCollection([
            new Translation('key2', 'en', 'World'),
            new Translation('key3', 'fr', 'Bonjour'),
        ]);

        $existing->addTranslations($newTranslations);

        expect($existing->count())->toBe(3);
    });

    it('does not add duplicate translations', function () {
        $existing = new TranslationCollection([
            new Translation('key1', 'en', 'Hello'),
        ]);

        $newTranslations = new TranslationCollection([
            new Translation('key1', 'en', 'Hello'),
        ]);

        $existing->addTranslations($newTranslations);

        expect($existing->count())->toBe(1);
    });

    it('can replace translations', function () {
        $existing = new TranslationCollection([
            new Translation('key1', 'en', 'Hello'),
        ]);

        $newTranslations = new TranslationCollection([
            new Translation('key1', 'en', 'Hello Updated'),
        ]);

        $existing->replaceTranslations($newTranslations);

        expect($existing->count())->toBe(1);
        expect($existing->first()->value)->toBe('Hello Updated');
    });

    it('can search for a translation', function () {
        $collection = new TranslationCollection([
            new Translation('key1', 'en', 'Hello'),
        ]);

        $translation = new Translation('key1', 'en', 'Hello');
        $key = $collection->searchTranslation($translation);

        expect($key)->toBeInt();
        expect($key)->toBe(0);
    });

    it('returns false when searching for a non-existent translation', function () {
        $collection = new TranslationCollection([
            new Translation('key1', 'en', 'Hello'),
        ]);

        $translation = new Translation('key2', 'en', 'World');
        $key = $collection->searchTranslation($translation);

        expect($key)->toBeFalse();
    });

    it('can sort translations', function () {
        $collection = new TranslationCollection([
            new Translation('key2', 'en', 'World'),
            new Translation('key1', 'en', 'Hello'),
        ]);

        $sorted = $collection->sortTranslations();

        expect($sorted->first()->key)->toBe('key1');
        expect($sorted->last()->key)->toBe('key2');
    });

    it('can filter by locale', function () {
        $collection = new TranslationCollection([
            new Translation('key1', 'en', 'Hello'),
            new Translation('key2', 'fr', 'Bonjour'),
        ]);

        $filtered = $collection->whereLocale('en');

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->key)->toBe('key1');
    });

    it('can filter by key', function () {
        $collection = new TranslationCollection([
            new Translation('key1', 'en', 'Hello'),
            new Translation('key2', 'fr', 'Bonjour'),
        ]);

        $filtered = $collection->whereKey('key1');

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->key)->toBe('key1');
    });

    it('can filter by group', function () {
        $collection = new TranslationCollection([
            new Translation('group.key1', 'en', 'Hello'),
            new Translation('group.key2', 'fr', 'Bonjour'),
            new Translation('othergroup.key1', 'en', 'Goodbye'),
        ]);

        $filtered = $collection->whereGroup('group');

        expect($filtered->count())->toBe(2);
    });

    it('can fake a collection of translations', function () {
        $collection = TranslationCollection::fake(5);

        expect($collection->count())->toBe(5);
        expect($collection->first())->toBeInstanceOf(Translation::class);
    });
})->group('TranslationCollection');
