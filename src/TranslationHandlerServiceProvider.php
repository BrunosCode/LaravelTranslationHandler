<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Commands\Command;
use BrunosCode\TranslationHandler\Commands\ExportCommand;
use BrunosCode\TranslationHandler\Commands\GetCommand;
use BrunosCode\TranslationHandler\Commands\ImportCommand;
use BrunosCode\TranslationHandler\Commands\SetCommand;
use BrunosCode\TranslationHandler\Mcp\Tools\FindTranslationTool;
use BrunosCode\TranslationHandler\Mcp\Tools\GetTranslationConfigTool;
use BrunosCode\TranslationHandler\Mcp\Tools\ListTranslationGroupsTool;
use BrunosCode\TranslationHandler\Mcp\Tools\ListTranslationsTool;
use BrunosCode\TranslationHandler\Mcp\Tools\SetAllLocalesTranslationTool;
use BrunosCode\TranslationHandler\Mcp\Tools\SetTranslationGroupTool;
use BrunosCode\TranslationHandler\Mcp\Tools\SetTranslationTool;
use BrunosCode\TranslationHandler\Mcp\Tools\SyncTranslationsTool;
use Laravel\Boost\Mcp\ToolRegistry;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
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
            ->name('laravel-translation-handler')
            ->hasConfigFile('translation-handler')
            ->hasMigrations([
                'create_translation_keys_table',
                'create_translation_values_table',
            ])
            ->hasCommands([
                Command::class,
                ImportCommand::class,
                ExportCommand::class,
                GetCommand::class,
                SetCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->copyAndRegisterServiceProviderInApp();
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(TranslationHandlerService::class, fn () => new TranslationHandlerService);
    }

    public function packageBooted(): void
    {
        $this->publishes([
            __DIR__.'/../resources/boost' => base_path('resources/boost'),
        ], 'translation-handler-boost');

        if (class_exists(ToolRegistry::class)) {
            config(['boost.mcp.tools.include' => array_merge(
                config('boost.mcp.tools.include', []),
                [
                    GetTranslationConfigTool::class,
                    ListTranslationsTool::class,
                    ListTranslationGroupsTool::class,
                    FindTranslationTool::class,
                    SetTranslationTool::class,
                    SetAllLocalesTranslationTool::class,
                    SetTranslationGroupTool::class,
                    SyncTranslationsTool::class,
                ]
            )]);
        }
    }
}
