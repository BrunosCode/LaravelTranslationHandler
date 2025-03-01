<?php

// config for BrunosCode/TranslationHandler

return [
    // used to break the key into parts for php files and json nested files
    // the first part in translations from php files will be the file name
    'keyDelimiter' => '.',

    // if locales is empty all app locales will be used
    'locales' => [],

    // PHP DEFAULTS

    // path for php files
    'phpPath' => lang_path(),

    // phpFileNames is used to get, set and delete only the selected files
    // if phpFileNames is empty, when setting, translations php files will be created for the first part of each key
    // if phpFileNames is empty, when getting, translations will be taken from all files in phpPath
    // if phpFileNames is empty, when deleting, all files in phpPath will be deleted
    'phpFileNames' => [],

    // if phpFormat is true php output will be formatted
    'phpFormat' => false,

    // CSV DEFAULTS

    // path for csv file
    'csvPath' => storage_path('lang'),
    // used as delimiter for csv file
    'csvDelimiter' => ';',
    // used as file name for csv file
    'csvFileName' => 'translations',

    // JSON DEFAULTS

    // path for json file
    'jsonPath' => lang_path(),

    // used as file name for json file
    // if jsonFileName is empty locale will be used
    // if jsonFileName is not empty locale will be used as folder
    'jsonFileName' => '',

    // if jsonNested is true json output will be nested as php file
    'jsonNested' => false,

    // if jsonFormat is true json output will be formatted
    'jsonFormat' => true,

    // DATABASE DEFAULTS

    // string used to identify the database connection
    // if dbConnection is null default connection will be used
    'dbConnection' => null,

    // HANDLERS DEFAULTS

    'phpHandlerClass' => \BrunosCode\TranslationHandler\PhpFileHandler::class,

    'csvHandlerClass' => \BrunosCode\TranslationHandler\CsvFileHandler::class,

    'jsonHandlerClass' => \BrunosCode\TranslationHandler\JsonFileHandler::class,

    'dbHandlerClass' => \BrunosCode\TranslationHandler\DatabaseHandler::class,

    // IMPORT/EXPORT DEFAULTS

    // defaultFromType is use as default fromType for ImportCommand and as default toType for ExportCommand
    'defaultFromType' => \BrunosCode\TranslationHandler\Data\TranslationOptions::PHP,

    // defaultFromPath is use as default fromPath for ImportCommand and as default toPath for ExportCommand
    // if defaultFromPath is null, the current path for the chosen type will be used
    'defaultFromPath' => null,

    // defaultFromFileNames is use as default fromFileNames for ImportCommand and as default toFileNames for ExportCommand
    // defaultFromFileNames must be an array if the chosen type is PHP, if not must be a string
    // if defaultFromFileNames is null, the current fileNames for the chosen type will be used
    'defaultFromFileNames' => null,

    // defaultToType is use as default toType for ImportCommand and as default fromType for ExportCommand
    'defaultToType' => \BrunosCode\TranslationHandler\Data\TranslationOptions::JSON,

    // defaultToPath is use as default toPath for ImportCommand and as default fromPath for ExportCommand
    // if defaultToPath is null, the current path for the chosen type will be used
    'defaultToPath' => null,

    // defaultToFileNames is use as default toFileNames for ImportCommand and as default fromFileNames for ExportCommand
    // defaultToFileNames must be an array if the chosen type is PHP, if not must be a string
    // if defaultToFileNames is null, the current fileNames for the chosen type will be used
    'defaultToFileNames' => null,
];
