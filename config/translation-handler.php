<?php

use BrunosCode\TranslationHandler\CsvFileHandler;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\DatabaseHandler;
use BrunosCode\TranslationHandler\JsonFileHandler;
use BrunosCode\TranslationHandler\PhpFileHandler;
use BrunosCode\TranslationHandler\TranslationChecker;

// config for BrunosCode/TranslationHandler

return [
    'keyDelimiter' => '.',

    'fileNames' => ['translation-handler'],
    'locales' => ['en'],

    'defaultImportFrom' => TranslationOptions::PHP,
    'defaultImportTo' => TranslationOptions::JSON,
    'defaultExportFrom' => TranslationOptions::JSON,
    'defaultExportTo' => TranslationOptions::PHP,

    'phpHandlerClass' => PhpFileHandler::class,
    'csvHandlerClass' => CsvFileHandler::class,
    'jsonHandlerClass' => JsonFileHandler::class,
    'dbHandlerClass' => DatabaseHandler::class,

    'phpFormat' => false,
    'phpPath' => lang_path(),

    'csvDelimiter' => ';',
    'csvFileName' => 'translations',
    'csvPath' => storage_path('lang'),

    'jsonPath' => lang_path(),
    // if jsonFileName is empty locale will be used
    // if jsonFileName is not empty locale will be used as folder
    'jsonFileName' => '',
    // if jsonNested is true json output will be nested as php file
    'jsonNested' => false,
    // if jsonFormat is true json output will be formatted
    'jsonFormat' => true,

    // Source scanned by translation-handler:check / check-translations-tool.
    // Each key is a "side"; paths are relative to the base path (absolute ok).
    // Optionally add a `patterns` entry per side to override the extraction
    // regexes — `static` patterns must capture a full key in group 1, `dynamic`
    // patterns a key prefix. When omitted, the bundled defaults are used (PHP
    // patterns for the `backend` side, JS/TS patterns for any other side).
    'check' => [
        'backend' => [
            'paths' => ['app', 'resources/views', 'routes', 'database'],
            'extensions' => ['php'],
        ],
        'frontend' => [
            'paths' => ['resources/js'],
            'extensions' => ['ts', 'tsx', 'js', 'jsx'],
        ],
    ],

    // Swap in a subclass of TranslationChecker to customise the scanning
    // regexes (override patternsFor()) or the defined-key resolution.
    'checkerClass' => TranslationChecker::class,
];
