<?php

// config for BrunosCode/TranslationHandler

return [
    'key_separator' => '.',
    'file_names' => [],
    'locales' => [],

    'default' => [
        'import_from' => \BrunosCode\TranslationHandler\TranslationHandlerService::PHP,
        'import_to' => \BrunosCode\TranslationHandler\TranslationHandlerService::DB,
    
        'export_from' => \BrunosCode\TranslationHandler\TranslationHandlerService::DB,
        'export_to' => \BrunosCode\TranslationHandler\TranslationHandlerService::PHP,
    ],
    
    'php_handler' => \BrunosCode\TranslationHandler\PhpFileHandler::class,
    'csv_handler' => \BrunosCode\TranslationHandler\CsvFileHandler::class,
    'json_handler' => \BrunosCode\TranslationHandler\JsonFileHandler::class,
    'db_handler' => \BrunosCode\TranslationHandler\DbHandler::class,
    
    'php_file' => [
        'format_export' => true,
    ],

    'db' => [
        'escape_values' => false,
        'key_table_name' => 'translation_keys',
        'value_table_name' => 'translation_values',
    ],

    'csv_file' => [
        'delimiter' => ';',
        'enclosure' => '"',
    ],

    'json_file' => [],

];
