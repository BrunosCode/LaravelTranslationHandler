<?php

namespace BrunosCode\TranslationHandler\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \BrunosCode\TranslationHandler\TranslationHandler
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
