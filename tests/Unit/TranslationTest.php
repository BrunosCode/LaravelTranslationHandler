<?php

use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;

describe('Translation', function () {
    it('can be instantiated with valid data', function () {
        $translation = new Translation('key1', 'en', 'Hello');

        expect($translation->key)->toBe('key1');
        expect($translation->locale)->toBe('en');
        expect($translation->value)->toBe('Hello');
    });

    it('throws an exception for invalid key', function () {
        new Translation('', 'en', 'Hello');
    })->throws(\InvalidArgumentException::class);

    it('throws an exception for invalid locale', function () {
        new Translation('key1', 'e', 'Hello');
    })->throws(\InvalidArgumentException::class);

    it('can convert to array', function () {
        $translation = new Translation('key1', 'en', 'Hello');

        expect($translation->toArray())->toBe([
            'key' => 'key1',
            'locale' => 'en',
            'value' => 'Hello',
        ]);
    });

    it('validates data using the validator', function () {
        $data = [
            'key' => 'key1',
            'locale' => 'en',
            'value' => 'Hello',
        ];

        $validator = Translation::validator($data);

        expect($validator->fails())->toBeFalse();
        expect($validator->validated())->toBe($data);
    });

    it('fails validation with invalid data', function () {
        $data = [
            'key' => '',
            'locale' => 'en',
            'value' => 'Hello',
        ];

        $validator = Translation::validator($data);

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('key'))->toBeTrue();
    });

    it('validates an array of translations using arrayValidator', function () {
        $data = [
            ['key' => 'key1', 'locale' => 'en', 'value' => 'Hello'],
            ['key' => 'key2', 'locale' => 'fr', 'value' => 'Bonjour'],
        ];

        $validator = Translation::arrayValidator($data);

        expect($validator->fails())->toBeFalse();
        expect($validator->validated())->toBe($data);
    });

    it('fails validation with invalid array of translations', function () {
        $data = [
            ['key' => '', 'locale' => 'en', 'value' => 'Hello'],
            ['key' => 'key2', 'locale' => 'fr', 'value' => 'Bonjour'],
        ];

        $validator = Translation::arrayValidator($data);

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('0.key'))->toBeTrue();
    });

    it('can fake a translation', function () {
        $translation = Translation::fake();

        TranslationHandler::shouldReceive('getOptions')->andReturn(new TranslationOptions());

        expect($translation)->toBeInstanceOf(Translation::class);
        expect($translation->key)->toContain(TranslationHandler::getOptions()->keyDelimiter);
        expect(TranslationHandler::getOptions()->locales)->toContain($translation->locale);
        expect($translation->value)->not->toBeEmpty();
    });
})->group('Translation');
