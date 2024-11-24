<?php

namespace BrunosCode\TranslationHandler\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool import(?string $from = null, ?string $to = null, bool $force = false, ?string $fromPath = null, ?string $toPath = null)
 * @method static bool export(?string $from = null, ?string $to = null, bool $force = false, ?string $fromPath = null, ?string $toPath = null)
 * @method static \BrunosCode\TranslationHandler\Collections\TranslationCollection get(string $from, ?string $path = null)
 * @method static int set(\BrunosCode\TranslationHandler\Collections\TranslationCollection $translations, string $to, ?string $path = null, bool $force = false)
 * @method static int delete(string $from, ?string $path = null)
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
    /**
     * @method static \BrunosCode\TranslationHandler\TranslationHandlerService store(array $translations, array $fileNames, array $locales, bool $force = false)
     * @method static \BrunosCode\TranslationHandler\TranslationHandlerService get(array $fileNames, array $locales)
     * @method static \BrunosCode\TranslationHandler\TranslationHandlerService getLocales()
     * @method static \BrunosCode\TranslationHandler\TranslationHandlerService getTypes()
     * @method static \BrunosCode\TranslationHandler\TranslationHandlerService getDefaultExportFrom()
     * @method static \BrunosCode\TranslationHandler\TranslationHandlerService getDefaultExportTo()
     * @method static \BrunosCode\TranslationHandler\TranslationHandlerService getDefaultImportFrom()
     * @method static \BrunosCode\TranslationHandler\TranslationHandlerService getDefaultImportTo()
     */
    protected static function getFacadeAccessor()
    {
        return \BrunosCode\TranslationHandler\TranslationHandlerService::class;
    }
}
