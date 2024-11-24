<?php

// config for BrunosCode/TranslationHandler

return [
    'keyDelimiter' => '.',

    'fileNames' => ['translation-handler'],
    'locales' => ['en'],

    'defaultImportFrom' => \BrunosCode\TranslationHandler\Data\TranslationOptions::PHP,
    'defaultImportTo' => \BrunosCode\TranslationHandler\Data\TranslationOptions::PHP,
    'defaultExportFrom' => \BrunosCode\TranslationHandler\Data\TranslationOptions::PHP,
    'defaultExportTo' => \BrunosCode\TranslationHandler\Data\TranslationOptions::PHP,

    'phpHandlerClass' => \BrunosCode\TranslationHandler\PhpFileHandler::class,
    'csvHandlerClass' => \BrunosCode\TranslationHandler\CsvFileHandler::class,
    'jsonHandlerClass' => \BrunosCode\TranslationHandler\JsonFileHandler::class,
    'dbHandlerClass' => \BrunosCode\TranslationHandler\DatabaseHandler::class,

    'phpFormat' => false,
    'phpPath' => lang_path(),

    'csvDelimiter' => ';',
    'csvFileName' => 'translations',
    'csvPath' => storage_path('lang'),

    'jsonPath' => lang_path(),
];
