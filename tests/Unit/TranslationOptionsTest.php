<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;

// Mock configuration data
function validConfig(): array
{
    return [
        'keyDelimiter' => '.',
        'fileNames' => ['app', 'auth', 'validation'],
        'locales' => ['en', 'es', 'fr'],
        'phpHandlerClass' => 'App\\Handlers\\PhpHandler',
        'dbHandlerClass' => 'App\\Handlers\\DbHandler',
        'csvHandlerClass' => 'App\\Handlers\\CsvHandler',
        'jsonHandlerClass' => 'App\\Handlers\\JsonHandler',
        'defaultImportFrom' => TranslationOptions::PHP,
        'defaultImportTo' => TranslationOptions::JSON,
        'defaultExportFrom' => TranslationOptions::CSV,
        'defaultExportTo' => TranslationOptions::DB,
        'phpPath' => 'resources/lang',
        'phpFormat' => true,
        'jsonPath' => 'resources/lang/json',
        'csvPath' => 'storage/csv',
        'csvFileName' => 'translations.csv',
        'csvDelimiter' => ',',
    ];
}

describe('TranslationOptions', function () {

    it('constructs successfully with valid configuration', function () {
        $options = new TranslationOptions;

        expect($options)
            ->toBeInstanceOf(TranslationOptions::class)
            ->keyDelimiter->toBe($this->testConfig()['keyDelimiter'])
            ->fileNames->toEqual($this->testConfig()['fileNames'])
            ->locales->toEqual($this->testConfig()['locales'])
            ->phpHandlerClass->toBe($this->testConfig()['phpHandlerClass'])
            ->dbHandlerClass->toBe($this->testConfig()['dbHandlerClass'])
            ->csvHandlerClass->toBe($this->testConfig()['csvHandlerClass'])
            ->jsonHandlerClass->toBe($this->testConfig()['jsonHandlerClass'])
            ->defaultImportFrom->toBe($this->testConfig()['defaultImportFrom'])
            ->defaultImportTo->toBe($this->testConfig()['defaultImportTo'])
            ->defaultExportFrom->toBe($this->testConfig()['defaultExportFrom'])
            ->defaultExportTo->toBe($this->testConfig()['defaultExportTo'])
            ->phpPath->toBe($this->testConfig()['phpPath'])
            ->phpFormat->toBe($this->testConfig()['phpFormat'])
            ->jsonPath->toBe($this->testConfig()['jsonPath'])
            ->csvPath->toBe($this->testConfig()['csvPath'])
            ->csvFileName->toBe($this->testConfig()['csvFileName'])
            ->csvDelimiter->toBe($this->testConfig()['csvDelimiter']);
    });

    it('throws an exception with invalid configuration', function () {
        config()->set('translation-handler', array_merge(validConfig(), [
            'keyDelimiter' => '', // Invalid key delimiter
        ]));

        new TranslationOptions;
    })->throws(InvalidArgumentException::class, 'The key delimiter field is required.');

    it('validates configuration with the validator', function () {
        $data = validConfig();

        $validator = (new TranslationOptions)->validator($data);

        expect($validator->fails())->toBeFalse();
    });

    it('fails validation with incorrect configuration', function () {
        $data = array_merge(validConfig(), [
            'locales' => ['en', 'e'], // Locale too short
        ]);

        $validator = (new TranslationOptions)->validator($data);

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first())->toBe('The locales.1 field must be at least 2 characters.');
    });
})->group('TranslationOptions');
