<?php

use BrunosCode\TranslationHandler\CsvFileHandler;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\DatabaseHandler;
use BrunosCode\TranslationHandler\JsonFileHandler;
use BrunosCode\TranslationHandler\PhpFileHandler;

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
];
