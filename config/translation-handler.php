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
    // if jsonFileName is empty locale will be used
    // if jsonFileName is not empty locale will be used as folder
    'jsonFileName' => '',
    // if jsonNested is true json output will be nested as php file
    'jsonNested' => false,
    // if jsonFormat is true json output will be formatted
    'jsonFormat' => true,
];
