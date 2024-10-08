<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Commands\TranslationHandlerExportCommand;
use BrunosCode\TranslationHandler\Commands\TranslationHandlerImportCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TranslationHandlerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laraveltranslationhandler')
            ->hasConfigFile('translation-handler')
            ->hasMigrations([
                'create_translation_keys_table',
                'create_translation_values_table',
            ])
            ->hasCommands([
                TranslationHandlerExportCommand::class,
                TranslationHandlerImportCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(TranslationHandlerService::class, function () {
            return new TranslationHandlerService(
                new PhpFileHandler,
                new CsvFileHandler,
                new JsonFileHandler,
                new DbHandler,
            );
        });
    }
}
