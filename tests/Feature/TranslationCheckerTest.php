<?php

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use BrunosCode\TranslationHandler\TranslationChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * A custom checker that recognises a project-specific `myTrans('...')` helper
 * instead of the built-in Laravel functions.
 */
class CustomPatternChecker extends TranslationChecker
{
    protected function patternsFor(string $side): array
    {
        return [
            'static' => ["/myTrans\\(\\s*'([^']+)'/"],
            'dynamic' => [],
        ];
    }
}

class PathProbeChecker extends TranslationChecker
{
    public function probe(string $path): bool
    {
        return $this->isAbsolutePath($path);
    }
}

describe('TranslationChecker path resolution', function () {
    it('detects absolute paths across platforms', function () {
        $checker = new PathProbeChecker(new TranslationOptions);

        // Unix
        expect($checker->probe('/var/www/app'))->toBeTrue();
        // Windows drive + UNC
        expect($checker->probe('C:\\sites\\app'))->toBeTrue();
        expect($checker->probe('C:/sites/app'))->toBeTrue();
        expect($checker->probe('\\\\server\\share'))->toBeTrue();
        // Relative
        expect($checker->probe('app'))->toBeFalse();
        expect($checker->probe('resources/views'))->toBeFalse();
    });
})->group('TranslationChecker');

describe('TranslationChecker custom class', function () {
    beforeEach(function () {
        $this->preparePhpTranslations();

        $dir = storage_path('checker-test');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0777, true);
        }
        File::put("{$dir}/Source.php", "<?php myTrans('test1.custom-missing');");

        TranslationHandler::setOption('check', [
            'backend' => ['paths' => [$dir], 'extensions' => ['php']],
            'frontend' => ['paths' => [], 'extensions' => ['php']],
        ]);
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
        File::deleteDirectory(storage_path('checker-test'));
    });

    it('resolves the checker class from config', function () {
        TranslationHandler::setOption('checkerClass', CustomPatternChecker::class);

        expect(TranslationHandler::getChecker())->toBeInstanceOf(CustomPatternChecker::class);
    });

    it('uses the custom patterns when the checker class is swapped', function () {
        TranslationHandler::setOption('checkerClass', CustomPatternChecker::class);

        $report = TranslationHandler::check(TranslationOptions::PHP, ['en'], ['backend']);

        expect($report['sides']['backend']['staticKeys'])->toBe(1);
        expect($report['sides']['backend']['locales']['en']['keys'])->toContain('test1.custom-missing');
    });

    it('ignores the custom helper with the default checker', function () {
        $report = TranslationHandler::check(TranslationOptions::PHP, ['en'], ['backend']);

        expect($report['sides']['backend']['staticKeys'])->toBe(0);
    });

    it('uses custom patterns declared in the config without subclassing', function () {
        $dir = storage_path('checker-test');

        TranslationHandler::setOption('check', [
            'backend' => [
                'paths' => [$dir],
                'extensions' => ['php'],
                'patterns' => [
                    'static' => ["/myTrans\\(\\s*'([^']+)'/"],
                    'dynamic' => [],
                ],
            ],
        ]);

        // Still the default checker class — patterns come purely from config.
        expect(TranslationHandler::getChecker())->toBeInstanceOf(TranslationChecker::class);

        $report = TranslationHandler::check(TranslationOptions::PHP, ['en'], ['backend']);

        expect($report['sides']['backend']['staticKeys'])->toBe(1);
        expect($report['sides']['backend']['locales']['en']['keys'])->toContain('test1.custom-missing');
    });

    it('derives the sides to scan from the configured check keys', function () {
        $dir = storage_path('checker-test');

        TranslationHandler::setOption('check', [
            'php' => ['paths' => [$dir], 'extensions' => ['php']],
            'twig' => ['paths' => [], 'extensions' => ['twig']],
        ]);

        expect(TranslationHandler::getChecker()->sides())->toBe(['php', 'twig']);

        // Omitting $sides scans every configured side.
        $report = TranslationHandler::check(TranslationOptions::PHP, ['en']);

        expect(array_keys($report['sides']))->toBe(['php', 'twig']);
    });
})->group('TranslationChecker', 'PhpFileHandler');

describe('TranslationChecker framework keys', function () {
    beforeEach(function () {
        $this->preparePhpTranslations();

        $dir = storage_path('framework-keys-test');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0777, true);
        }
        // auth.failed lives in Laravel's bundled lang files, never in the
        // package's own fileNames (test1 / test2).
        File::put("{$dir}/Source.php", "<?php echo __('auth.failed');");

        TranslationHandler::setOption('check', [
            'backend' => ['paths' => [$dir], 'extensions' => ['php']],
            'frontend' => ['paths' => [], 'extensions' => ['php']],
        ]);
    });

    afterEach(function () {
        $this->cleanPhpTranslations();
        File::deleteDirectory(storage_path('framework-keys-test'));
    });

    it('reports a bundled framework key as missing by default', function () {
        // checkIncludeFrameworkKeys defaults to false.
        $report = TranslationHandler::check(TranslationOptions::PHP, ['en'], ['backend']);

        expect($report['sides']['backend']['locales']['en']['keys'])->toContain('auth.failed');
    });

    it('treats bundled framework keys as defined when enabled', function () {
        TranslationHandler::setOption('checkIncludeFrameworkKeys', true);

        $report = TranslationHandler::check(TranslationOptions::PHP, ['en'], ['backend']);

        expect($report['sides']['backend']['locales']['en']['keys'])->not->toContain('auth.failed');
        expect($report['sides']['backend']['locales']['en']['total'])->toBe(0);
    });
})->group('TranslationChecker', 'PhpFileHandler');
