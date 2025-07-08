<?php

namespace BrunosCode\TranslationHandler\Tests;

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\TranslationHandlerServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'BrunosCode\\TranslationHandler\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->app['config']->set('translation-handler', $this->config());
    }

    protected function config(array $config = [])
    {
        return array_merge([
            'keyDelimiter' => '.',

            'fileNames' => ['test1', 'test2'],
            'locales' => ['en', 'it'],
            'defaultImportFrom' => \BrunosCode\TranslationHandler\Data\TranslationOptions::PHP,
            'defaultImportTo' => \BrunosCode\TranslationHandler\Data\TranslationOptions::JSON,
            'defaultExportFrom' => \BrunosCode\TranslationHandler\Data\TranslationOptions::JSON,
            'defaultExportTo' => \BrunosCode\TranslationHandler\Data\TranslationOptions::PHP,
            'phpHandlerClass' => \BrunosCode\TranslationHandler\PhpFileHandler::class,
            'csvHandlerClass' => \BrunosCode\TranslationHandler\CsvFileHandler::class,
            'jsonHandlerClass' => \BrunosCode\TranslationHandler\JsonFileHandler::class,
            'dbHandlerClass' => \BrunosCode\TranslationHandler\DatabaseHandler::class,
            'phpPath' => lang_path('php-test'),
            'phpFormat' => false,
            'csvDelimiter' => ';',
            'csvFileName' => 'test-translations',
            'csvPath' => storage_path('lang/csv-test'),
            'jsonPath' => lang_path('json-test'),
            'jsonFileName' => 'test-translations',
            'jsonNested' => false,
            'jsonFormat' => true,
        ], $config);
    }

    protected function getPackageProviders($app)
    {
        return [
            TranslationHandlerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'sqlite');
    }

    public function prepareService()
    {
        $options = new TranslationOptions;
        TranslationHandler::shouldReceive('getDefaultOptions')->andReturn($options);

        $phpHandler = app($options->phpHandlerClass, [$options]);
        TranslationHandler::shouldReceive('getPhpHandler')->andReturn($phpHandler);

        $jsonHandler = app($options->jsonHandlerClass, [$options]);
        TranslationHandler::shouldReceive('getJsonHandler')->andReturn($jsonHandler);

        $csvHandler = app($options->csvHandlerClass, [$options]);
        TranslationHandler::shouldReceive('getCsvHandler')->andReturn($csvHandler);

        $dbHandler = app($options->dbHandlerClass, [$options]);
        TranslationHandler::shouldReceive('getDbHandler')->andReturn($dbHandler);
    }

    public function preparePhpTranslations()
    {
        $options = TranslationHandler::getOptions();

        if (! File::exists($options->phpPath)) {
            File::makeDirectory($options->phpPath, 0777, true);
        }

        foreach ($options->locales as $locale) {
            if (! File::exists($options->phpPath.'/'.$locale)) {
                File::makeDirectory($options->phpPath.'/'.$locale, 0777, true);
            }

            foreach ($options->fileNames as $filename) {
                File::put(
                    "{$options->phpPath}/{$locale}/{$filename}.php",
                    '<?php return '.var_export([
                        'get' => "get-1-{$locale}",
                        'nested' => [
                            'get' => "get-2-{$locale}",
                        ],
                    ], true).';'
                );
            }
        }
    }

    public function cleanPhpTranslations()
    {
        File::deleteDirectory(TranslationHandler::getOptions()->phpPath);
    }

    public function prepareJsonTranslations()
    {
        $options = TranslationHandler::getOptions();

        if (! File::exists($options->jsonPath)) {
            File::makeDirectory($options->jsonPath, 0777, true);
        }

        foreach ($options->locales as $locale) {
            $path = ! empty($options->jsonFileName)
                ? "{$options->jsonPath}/{$locale}/{$options->jsonFileName}.json"
                : "{$options->jsonPath}/{$locale}.json";

            if (! File::exists(dirname($path))) {
                File::makeDirectory(dirname($path), 0777, true);
            }

            File::put(
                $path,
                json_encode([
                    'test1.get' => "get-1-{$locale}",
                    'test2.get' => "get-2-{$locale}",
                    'test1' => [
                        'nested' => [
                            'get' => "get-1-{$locale}",
                        ],
                    ],
                    'test2' => [
                        'nested' => [
                            'get' => "get-2-{$locale}",
                        ],
                    ],
                ]),
                false
            );
        }
    }

    public function cleanJsonTranslations()
    {
        $options = TranslationHandler::getOptions();
        foreach ($options->locales as $locale) {
            $path = ! empty($options->jsonFileName)
                ? "{$options->jsonPath}/{$locale}/{$options->jsonFileName}.json"
                : "{$options->jsonPath}/{$locale}.json";

            File::delete($path);
        }
    }

    public function prepareCsvTranslations()
    {
        $options = TranslationHandler::getOptions();

        if (! File::exists($options->csvPath)) {
            File::makeDirectory($options->csvPath, 0777, true);
        }

        File::put(
            "{$options->csvPath}/{$options->csvFileName}.csv",
            "key;en;it\ntest1.get;get-1-en;get-1-it\ntest2.get;get-2-en;get-2-it"
        );
    }

    public function cleanCsvTranslations()
    {
        $options = TranslationHandler::getOptions();
        File::delete("{$options->csvPath}/{$options->csvFileName}.csv");
    }

    public function prepareDbTranslations()
    {
        DB::table('translation_keys')->insert([
            [
                'key' => 'test1.get',
                'updated_at' => now(),
                'created_at' => now(),
            ],
            [
                'key' => 'test2.get',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        ]);

        DB::table('translation_values')->insert([
            [
                'translation_key_id' => 1,
                'locale' => 'en',
                'value' => 'get-1-en',
                'updated_at' => now(),
                'created_at' => now(),
            ],
            [
                'translation_key_id' => 1,
                'locale' => 'it',
                'value' => 'get-1-it',
                'updated_at' => now(),
                'created_at' => now(),
            ],
            [
                'translation_key_id' => 2,
                'locale' => 'en',
                'value' => 'get-2-en',
                'updated_at' => now(),
                'created_at' => now(),
            ],
            [
                'translation_key_id' => 2,
                'locale' => 'it',
                'value' => 'get-2-it',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        ]);
    }

    public function cleanDbTranslations()
    {
        DB::table('translation_keys')->delete();
        DB::table('translation_values')->delete();
    }
}
