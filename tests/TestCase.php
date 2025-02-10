<?php

namespace BrunosCode\TranslationHandler\Tests;

use BrunosCode\TranslationHandler\TranslationHandlerServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'BrunosCode\\TranslationHandler\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->prepareForTests();
    }

    private function prepareForTests()
    {
        $this->app['config']->set('translation-handler', $this->test_config());
        Artisan::call('migrate');
    }

    protected function test_config(array $config = [])
    {
        return array_merge([
            'keyDelimiter' => '.',

            'fileNames' => ['test1', 'test2'],
            'locales' => ['en', 'it'],
            'defaultImportFrom' => \BrunosCode\TranslationHandler\Data\TranslationOptions::PHP,
            'defaultImportTo' => \BrunosCode\TranslationHandler\Data\TranslationOptions::PHP,
            'defaultExportFrom' => \BrunosCode\TranslationHandler\Data\TranslationOptions::PHP,
            'defaultExportTo' => \BrunosCode\TranslationHandler\Data\TranslationOptions::PHP,
            'phpHandlerClass' => \BrunosCode\TranslationHandler\PhpFileHandler::class,
            'csvHandlerClass' => \BrunosCode\TranslationHandler\CsvFileHandler::class,
            'jsonHandlerClass' => \BrunosCode\TranslationHandler\JsonFileHandler::class,
            'dbHandlerClass' => \BrunosCode\TranslationHandler\DatabaseHandler::class,
            'phpFormat' => false,
            'phpPath' => lang_path('test'),
            'csvDelimiter' => ';',
            'csvFileName' => 'test-translations',
            'csvPath' => storage_path('lang/test'),
            'jsonPath' => lang_path('test'),
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
        config()->set('database.default', 'testing');
    }
}
