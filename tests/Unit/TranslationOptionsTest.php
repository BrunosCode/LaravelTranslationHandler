<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;

describe('TranslationOptions', function () {

    it('constructs successfully with valid configuration', function () {
        $options = new TranslationOptions();

        expect($options)
            ->toBeInstanceOf(TranslationOptions::class)
            ->keyDelimiter->toBe($this->config()['keyDelimiter'])
            ->fileNames->toEqual($this->config()['fileNames'])
            ->locales->toEqual($this->config()['locales'])
            ->phpHandlerClass->toBe($this->config()['phpHandlerClass'])
            ->dbHandlerClass->toBe($this->config()['dbHandlerClass'])
            ->csvHandlerClass->toBe($this->config()['csvHandlerClass'])
            ->jsonHandlerClass->toBe($this->config()['jsonHandlerClass'])
            ->defaultImportFrom->toBe($this->config()['defaultImportFrom'])
            ->defaultImportTo->toBe($this->config()['defaultImportTo'])
            ->defaultExportFrom->toBe($this->config()['defaultExportFrom'])
            ->defaultExportTo->toBe($this->config()['defaultExportTo'])
            ->phpPath->toBe($this->config()['phpPath'])
            ->phpFormat->toBe($this->config()['phpFormat'])
            ->jsonPath->toBe($this->config()['jsonPath'])
            ->jsonFileName->toBe($this->config()['jsonFileName'])
            ->jsonNested->toBe($this->config()['jsonNested'])
            ->jsonFormat->toBe($this->config()['jsonFormat'])
            ->csvPath->toBe($this->config()['csvPath'])
            ->csvFileName->toBe($this->config()['csvFileName'])
            ->csvDelimiter->toBe($this->config()['csvDelimiter']);
    });

    it('throws an exception with invalid configuration', function () {
        config()->set('translation-handler', array_merge($this->config(), [
            'keyDelimiter' => '', // Invalid key delimiter
        ]));

        new TranslationOptions();
    })->throws(InvalidArgumentException::class, 'The key delimiter field is required.');

    it('validates configuration with the validator', function () {
        $data = $this->config();

        $validator = (new TranslationOptions())->validator($data);

        expect($validator->fails())->toBeFalse();
    });

    it('fails validation with incorrect configuration', function () {
        $data = array_merge($this->config(), [
            'locales' => ['en', 'e'], // Locale too short
        ]);

        $validator = (new TranslationOptions())->validator($data);

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first())->toBe('The locales.1 field must be at least 2 characters.');
    });
})->group('TranslationOptions');
