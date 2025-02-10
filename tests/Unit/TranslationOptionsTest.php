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
            ->keyDelimiter->toBe($this->test_config()['keyDelimiter'])
            ->fileNames->toEqual($this->test_config()['fileNames'])
            ->locales->toEqual($this->test_config()['locales'])
            ->phpHandlerClass->toBe($this->test_config()['phpHandlerClass'])
            ->dbHandlerClass->toBe($this->test_config()['dbHandlerClass'])
            ->csvHandlerClass->toBe($this->test_config()['csvHandlerClass'])
            ->jsonHandlerClass->toBe($this->test_config()['jsonHandlerClass'])
            ->defaultImportFrom->toBe($this->test_config()['defaultImportFrom'])
            ->defaultImportTo->toBe($this->test_config()['defaultImportTo'])
            ->defaultExportFrom->toBe($this->test_config()['defaultExportFrom'])
            ->defaultExportTo->toBe($this->test_config()['defaultExportTo'])
            ->phpPath->toBe($this->test_config()['phpPath'])
            ->phpFormat->toBe($this->test_config()['phpFormat'])
            ->jsonPath->toBe($this->test_config()['jsonPath'])
            ->csvPath->toBe($this->test_config()['csvPath'])
            ->csvFileName->toBe($this->test_config()['csvFileName'])
            ->csvDelimiter->toBe($this->test_config()['csvDelimiter']);
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
