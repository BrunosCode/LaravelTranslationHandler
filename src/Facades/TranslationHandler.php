<?php

namespace BrunosCode\TranslationHandler\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool move(bool $overwrite, string $fromType, string $toType, ?string $fromPath = null, ?string $toPath = null, null|string|array $fromFileNames = null, null|string|array $toFileNames = null)
 * @method static \BrunosCode\TranslationHandler\Collections\TranslationCollection get(string $type, ?string $path = null, null|string|array $fileNames = null)
 * @method static int set(\BrunosCode\TranslationHandler\Collections\TranslationCollection $translations, string $type, ?string $path = null, null|string|array $fileNames = null, bool $force = false)
 * @method static int delete(string $from, ?string $path = null, null|string|array $fileNames = null)
 * @method static array getTypes()
 * @method static \BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface getPhpHandler()
 * @method static \BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface getCsvHandler()
 * @method static \BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface getJsonHandler()
 * @method static \BrunosCode\TranslationHandler\Interfaces\DatabaseHandlerInterface getDbHandler()
 * @method static \BrunosCode\TranslationHandler\Data\TranslationOptions getOptions()
 * @method static self setOptions(\BrunosCode\TranslationHandler\Data\TranslationOptions $options)
 * @method static self resetOptions()
 * @method static self setOption(string $name, mixed $value)
 * @method static mixed getOption(string $name)
 * @method static \BrunosCode\TranslationHandler\Data\TranslationOptions getDefaultOptions()
 * @method static self setDefaultOptions(\BrunosCode\TranslationHandler\Data\TranslationOptions $options)
 * @method static self setDefaultOption(string $name, mixed $value)
 * @method static mixed getDefaultOption(string $name)
 *
 * @see \BrunosCode\TranslationHandler\TranslationHandlerService
 */
class TranslationHandler extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \BrunosCode\TranslationHandler\TranslationHandlerService::class;
    }
}
