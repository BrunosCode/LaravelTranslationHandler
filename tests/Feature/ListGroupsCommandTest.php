<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ListGroupsCommand common', function () {
    it('asks questions if no arguments are provided', function () {
        $this->artisan('translation-handler:list-groups')
            ->expectsQuestion('From where do you want to import translations?', TranslationOptions::PHP);
    });

    it('throws error if no from argument is provided', function () {
        $this->artisan('translation-handler:list-groups')
            ->expectsQuestion('From where do you want to import translations?', '');
    })->throws(InvalidArgumentException::class);
})->group('ListGroupsCommand');

describe('ListGroupsCommand php', function () {
    beforeEach(function () {
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('lists level-0 groups by default', function () {
        $this->artisan('translation-handler:list-groups', ['from' => TranslationOptions::PHP])
            ->expectsOutput('test1')
            ->expectsOutput('test2')
            ->expectsOutput('Total: 2')
            ->assertSuccessful();
    });

    it('lists level-1 groups', function () {
        $this->artisan('translation-handler:list-groups', [
            'from' => TranslationOptions::PHP,
            '--level' => 1,
        ])
            ->expectsOutput('test1.nested')
            ->expectsOutput('test2.nested')
            ->expectsOutput('Total: 2')
            ->assertSuccessful();
    });

    it('filters groups by search', function () {
        $this->artisan('translation-handler:list-groups', [
            'from' => TranslationOptions::PHP,
            '--search' => 'test1',
        ])
            ->expectsOutput('test1')
            ->expectsOutput('Total: 1')
            ->assertSuccessful();
    });

    it('search is case-insensitive', function () {
        $this->artisan('translation-handler:list-groups', [
            'from' => TranslationOptions::PHP,
            '--search' => 'TEST1',
        ])
            ->expectsOutput('test1')
            ->expectsOutput('Total: 1')
            ->assertSuccessful();
    });

    it('returns zero results for non-matching search', function () {
        $this->artisan('translation-handler:list-groups', [
            'from' => TranslationOptions::PHP,
            '--search' => 'nonexistent',
        ])
            ->expectsOutput('Total: 0')
            ->assertSuccessful();
    });

    it('returns no groups when all keys are too shallow for the level', function () {
        $this->artisan('translation-handler:list-groups', [
            'from' => TranslationOptions::PHP,
            '--level' => 99,
        ])
            ->expectsOutput('Total: 0')
            ->assertSuccessful();
    });
})->group('ListGroupsCommand', 'PhpFileHandler');

describe('ListGroupsCommand csv', function () {
    beforeEach(function () {
        $this->prepareCsvTranslations();
    });

    afterEach(function () {
        $this->cleanCsvTranslations();
    });

    it('lists level-0 groups', function () {
        $this->artisan('translation-handler:list-groups', ['from' => TranslationOptions::CSV])
            ->expectsOutput('test1')
            ->expectsOutput('test2')
            ->expectsOutput('Total: 2')
            ->assertSuccessful();
    });

    it('returns no level-1 groups when keys only have two segments', function () {
        $this->artisan('translation-handler:list-groups', [
            'from' => TranslationOptions::CSV,
            '--level' => 1,
        ])
            ->expectsOutput('Total: 0')
            ->assertSuccessful();
    });
})->group('ListGroupsCommand', 'CsvFileHandler');

describe('ListGroupsCommand database', function () {
    beforeEach(function () {
        $this->prepareDbTranslations();
    });

    afterEach(function () {
        $this->cleanDbTranslations();
    });

    it('lists level-0 groups', function () {
        $this->artisan('translation-handler:list-groups', ['from' => TranslationOptions::DB])
            ->expectsOutput('test1')
            ->expectsOutput('test2')
            ->expectsOutput('Total: 2')
            ->assertSuccessful();
    });
})->group('ListGroupsCommand', 'DatabaseHandler');
