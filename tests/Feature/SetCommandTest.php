<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('SetCommand common', function () {
    it('asks questions if no arguments are provided', function () {
        $this->artisan('translation-handler:set')
            ->expectsQuestion('To where do you want to export translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', 'test1.put')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsQuestion('What is the translation value?', 'put-1-it');
    });

    it('throws error if no from argument is provided', function () {
        $this->artisan('translation-handler:set')
            ->expectsQuestion('To where do you want to export translations?', '')
            ->expect();
    })->throws(InvalidArgumentException::class);

    it('throws error if no key argument is provided', function () {
        $this->artisan('translation-handler:set')
            ->expectsQuestion('To where do you want to export translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', '')
            ->assertFailed();
    })->throws(InvalidArgumentException::class);

    it('throws error if no locale argument is provided', function () {
        $this->artisan('translation-handler:set')
            ->expectsQuestion('To where do you want to export translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', 'test1.put')
            ->expectsQuestion('What is the translation locale?', '')
            ->assertFailed();
    })->throws(InvalidArgumentException::class);
})->group('SetCommand');

describe('SetCommand php', function () {
    beforeEach(function () {
        $this->preparePhpTranslations();
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
    });

    it('can set translation if it does not exist', function () {
        $this->artisan('translation-handler:set')
            ->expectsQuestion('To where do you want to export translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', 'test1.put')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsQuestion('What is the translation value?', 'new-translation')
            ->expectsOutput('Setting translation...')
            ->expectsOutput('Translation set!')
            ->assertSuccessful();

        expect(
            TranslationHandler::get(TranslationOptions::PHP)
                ->whereKey('test1.put')
                ->whereLocale('it')
                ->first()
                ?->value
        )->toBe('new-translation');
    });

    it('cannot set translation if it exists', function () {
        $this->artisan('translation-handler:set')
            ->expectsQuestion('To where do you want to export translations?', TranslationOptions::PHP)
            ->expectsQuestion('What is the translation key?', 'test1.put')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsQuestion('What is the translation value?', 'new-translation')
            ->expectsOutput('Setting translation...')
            ->expectsOutput('Translation set!')
            ->assertSuccessful();

        expect(
            TranslationHandler::get(TranslationOptions::PHP)
                ->whereKey('test1.put')
                ->whereLocale('it')
                ->first()
                ?->value
        )->toBe('new-translation');
    });
})->group('SetCommand', 'PhpFileHandler');

describe('SetCommand json', function () {
    beforeEach(function () {
        $this->prepareJsonTranslations();
    });

    afterEach(function () {
        $this->cleanJsonTranslations();
    });

    it('can set translation if it does not exist', function () {
        $this->artisan('translation-handler:set')
            ->expectsQuestion('To where do you want to export translations?', TranslationOptions::JSON)
            ->expectsQuestion('What is the translation key?', 'test1.put')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsQuestion('What is the translation value?', 'new-translation')
            ->expectsOutput('Setting translation...')
            ->expectsOutput('Translation set!')
            ->assertSuccessful();

        expect(
            TranslationHandler::get(TranslationOptions::JSON)
                ->whereKey('test1.put')
                ->whereLocale('it')
                ->first()
                ?->value
        )->toBe('new-translation');
    });

    it('cannot set translation if it exists', function () {
        $this->artisan('translation-handler:set')
            ->expectsQuestion('To where do you want to export translations?', TranslationOptions::JSON)
            ->expectsQuestion('What is the translation key?', 'test1.put')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsQuestion('What is the translation value?', 'new-translation')
            ->expectsOutput('Setting translation...')
            ->expectsOutput('Translation set!')
            ->assertSuccessful();

        expect(
            TranslationHandler::get(TranslationOptions::JSON)
                ->whereKey('test1.put')
                ->whereLocale('it')
                ->first()
                ?->value
        )->toBe('new-translation');
    });
})->group('SetCommand', 'JsonFileHandler');

describe('SetCommand csv', function () {
    // beforeEach(function () {
    //   $this->prepareCsvTranslations();
    // });

    afterEach(function () {
        $this->cleanCsvTranslations();
    });

    it('can set translation if it does not exist', function () {
        $this->artisan('translation-handler:set')
            ->expectsQuestion('To where do you want to export translations?', TranslationOptions::CSV)
            ->expectsQuestion('What is the translation key?', 'test1.put')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsQuestion('What is the translation value?', 'new-translation')
            ->expectsOutput('Setting translation...')
            ->expectsOutput('Translation set!')
            ->assertSuccessful();

        expect(
            TranslationHandler::get(TranslationOptions::CSV)
                ->whereKey('test1.put')
                ->whereLocale('it')
                ->first()
                ?->value
        )->toBe('new-translation');
    });

    it('cannot set translation if it exists', function () {
        $this->artisan('translation-handler:set')
            ->expectsQuestion('To where do you want to export translations?', TranslationOptions::CSV)
            ->expectsQuestion('What is the translation key?', 'test1.put')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsQuestion('What is the translation value?', 'new-translation')
            ->expectsOutput('Setting translation...')
            ->expectsOutput('Translation set!')
            ->assertSuccessful();

        expect(
            TranslationHandler::get(TranslationOptions::CSV)
                ->whereKey('test1.put')
                ->whereLocale('it')
                ->first()
                ?->value
        )->toBe('new-translation');
    });
})->group('SetCommand', 'CsvFileHandler');

describe('SetCommand database', function () {
    beforeEach(function () {
        $this->prepareDbTranslations();
    });

    afterEach(function () {
        $this->cleanDbTranslations();
    });

    it('can set translation if it does not exist', function () {
        $this->artisan('translation-handler:set')
            ->expectsQuestion('To where do you want to export translations?', TranslationOptions::DB)
            ->expectsQuestion('What is the translation key?', 'test1.put')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsQuestion('What is the translation value?', 'new-translation')
            ->expectsOutput('Setting translation...')
            ->expectsOutput('Translation set!')
            ->assertSuccessful();

        expect(
            TranslationHandler::get(TranslationOptions::DB)
                ->whereKey('test1.put')
                ->whereLocale('it')
                ->first()
                ?->value
        )->toBe('new-translation');
    });

    it('cannot set translation if it exists', function () {
        $this->artisan('translation-handler:set')
            ->expectsQuestion('To where do you want to export translations?', TranslationOptions::DB)
            ->expectsQuestion('What is the translation key?', 'test1.put')
            ->expectsQuestion('What is the translation locale?', 'it')
            ->expectsQuestion('What is the translation value?', 'new-translation')
            ->expectsOutput('Setting translation...')
            ->expectsOutput('Translation set!')
            ->assertSuccessful();

        expect(
            TranslationHandler::get(TranslationOptions::DB)
                ->whereKey('test1.put')
                ->whereLocale('it')
                ->first()
                ?->value
        )->toBe('new-translation');
    });
})->group('SetCommand', 'DatabaseHandler');
