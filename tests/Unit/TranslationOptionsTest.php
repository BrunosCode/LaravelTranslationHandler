<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\TranslationChecker;

describe('TranslationOptions', function () {

    it('constructs successfully with valid configuration', function () {
        $options = new TranslationOptions;

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
            ->phpPint->toBe($this->config()['phpPint'])
            ->jsonPath->toBe($this->config()['jsonPath'])
            ->jsonFileName->toBe($this->config()['jsonFileName'])
            ->jsonNested->toBe($this->config()['jsonNested'])
            ->jsonFormat->toBe($this->config()['jsonFormat'])
            ->csvPath->toBe($this->config()['csvPath'])
            ->csvFileName->toBe($this->config()['csvFileName'])
            ->csvDelimiter->toBe($this->config()['csvDelimiter']);
    });

    it('defaults phpPint to false when the key is absent (config published before the option existed)', function () {
        $config = $this->config();
        unset($config['phpPint']);

        $options = new TranslationOptions($config);

        expect($options->phpPint)->toBeFalse();
    });

    it('throws an exception with invalid configuration', function () {
        config()->set('translation-handler', array_merge($this->config(), [
            'keyDelimiter' => '', // Invalid key delimiter
        ]));

        new TranslationOptions;
    })->throws(InvalidArgumentException::class, 'The key delimiter field is required.');

    it('validates configuration with the validator', function () {
        $data = $this->config();

        $validator = (new TranslationOptions)->validator($data);

        expect($validator->fails())->toBeFalse();
    });

    it('fails validation with incorrect configuration', function () {
        $data = array_merge($this->config(), [
            'locales' => ['en', 'e'], // Locale too short
        ]);

        $validator = (new TranslationOptions)->validator($data);

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first())->toBe('The locales.1 field must be at least 2 characters.');
    });

    it('reads the checker class from config', function () {
        expect((new TranslationOptions)->checkerClass)->toBe(TranslationChecker::class);
    });

    it('throws when the check option is missing', function () {
        $config = $this->config();
        unset($config['check']);
        config()->set('translation-handler', $config);

        new TranslationOptions;
    })->throws(InvalidArgumentException::class);

    it('throws when the checker class is missing', function () {
        $config = $this->config();
        unset($config['checkerClass']);
        config()->set('translation-handler', $config);

        new TranslationOptions;
    })->throws(InvalidArgumentException::class);

    it('keeps a custom checker class provided via config', function () {
        config()->set('translation-handler', array_merge($this->config(), [
            'checkerClass' => 'App\\Custom\\Checker',
        ]));

        expect((new TranslationOptions)->checkerClass)->toBe('App\\Custom\\Checker');
    });

    it('keeps a custom check option provided via config', function () {
        config()->set('translation-handler', array_merge($this->config(), [
            'check' => [
                'backend' => ['paths' => ['src'], 'extensions' => ['php']],
                'frontend' => ['paths' => [], 'extensions' => ['vue']],
            ],
        ]));

        $options = new TranslationOptions;

        expect($options->check['backend']['paths'])->toBe(['src']);
        expect($options->check['frontend']['extensions'])->toBe(['vue']);
    });

    it('fails validation when a check side is malformed', function () {
        $data = array_merge($this->config(), [
            'check' => [
                'backend' => ['paths' => ['app']], // extensions key missing
            ],
        ]);

        $validator = (new TranslationOptions)->validator($data);

        expect($validator->fails())->toBeTrue();
    });

    it('accepts valid custom patterns in the check option', function () {
        $data = array_merge($this->config(), [
            'check' => [
                'backend' => [
                    'paths' => ['app'],
                    'extensions' => ['php'],
                    'patterns' => [
                        'static' => ["/__\\(\\s*'([^']+)'/"],
                        'dynamic' => [],
                    ],
                ],
            ],
        ]);

        $validator = (new TranslationOptions)->validator($data);

        expect($validator->fails())->toBeFalse();
    });

    it('fails validation when a check pattern is not a valid regex', function () {
        $data = array_merge($this->config(), [
            'check' => [
                'backend' => [
                    'paths' => ['app'],
                    'extensions' => ['php'],
                    'patterns' => [
                        'static' => ['/unterminated('],
                    ],
                ],
            ],
        ]);

        $validator = (new TranslationOptions)->validator($data);

        expect($validator->fails())->toBeTrue();
    });

    it('validates arbitrary side names with a valid structure', function () {
        config()->set('translation-handler', array_merge($this->config(), [
            'check' => [
                'blade' => ['paths' => ['resources/views'], 'extensions' => ['php']],
                'vue' => ['paths' => ['resources/js'], 'extensions' => ['vue']],
            ],
        ]));

        $options = new TranslationOptions;

        expect(array_keys($options->check))->toBe(['blade', 'vue']);
    });
})->group('TranslationOptions');
