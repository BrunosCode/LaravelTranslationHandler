<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * Write a fixture source file and point the backend scanner at its directory.
 */
function prepareCheckSource(string $contents, string $filename = 'Source.php'): string
{
    $dir = storage_path('check-test');

    if (! File::exists($dir)) {
        File::makeDirectory($dir, 0777, true);
    }

    File::put("{$dir}/{$filename}", $contents);

    TranslationHandler::setOption('check', [
        'backend' => ['paths' => [$dir], 'extensions' => ['php']],
        'frontend' => ['paths' => [], 'extensions' => ['ts', 'tsx', 'js', 'jsx']],
    ]);

    return $dir;
}

function cleanCheckSource(): void
{
    File::deleteDirectory(storage_path('check-test'));
}

describe('CheckCommand common', function () {
    it('asks for the source format if no argument is provided', function () {
        $this->artisan('translation-handler:check')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP);
    });

    it('rejects an invalid --side', function () {
        $this->artisan('translation-handler:check', [
            'from' => TranslationOptions::PHP,
            '--side' => 'sideways',
        ])
            ->expectsOutput('--side must be one of: backend, frontend')
            ->assertFailed();
    });
})->group('CheckCommand');

describe('CheckCommand php', function () {
    beforeEach(function () {
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
        cleanCheckSource();
    });

    it('passes when every referenced key is defined', function () {
        prepareCheckSource(<<<'PHP'
        <?php
        echo __('test1.get');
        echo __('test2.get');
        PHP);

        $this->artisan('translation-handler:check', [
            'from' => TranslationOptions::PHP,
            '--side' => 'backend',
        ])
            ->assertSuccessful();
    });

    it('fails and lists a missing key', function () {
        prepareCheckSource(<<<'PHP'
        <?php
        echo __('test1.get');
        echo __('test1.does.not.exist');
        PHP);

        $this->artisan('translation-handler:check', [
            'from' => TranslationOptions::PHP,
            '--side' => 'backend',
            '--show-keys' => true,
        ])
            ->expectsOutputToContain('test1.does.not.exist')
            ->assertFailed();
    });

    it('restricts the report to a single locale', function () {
        prepareCheckSource(<<<'PHP'
        <?php
        echo __('test1.get');
        PHP);

        $this->artisan('translation-handler:check', [
            'from' => TranslationOptions::PHP,
            '--side' => 'backend',
            '--locale' => ['en'],
        ])
            ->assertSuccessful();
    });

    it('lists orphan keys when --orphans is set', function () {
        prepareCheckSource(<<<'PHP'
        <?php
        echo __('test1.get');
        PHP);

        $this->artisan('translation-handler:check', [
            'from' => TranslationOptions::PHP,
            '--side' => 'backend',
            '--orphans' => true,
            '--show-keys' => true,
        ])
            // test2.get is defined but never referenced in the scanned source.
            // Orphans are informational only — they do not change the exit code.
            ->expectsOutputToContain('test2.get')
            ->assertSuccessful();
    });
})->group('CheckCommand', 'PhpFileHandler');
