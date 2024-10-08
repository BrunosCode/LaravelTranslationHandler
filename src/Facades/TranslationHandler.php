<?php

namespace BrunosCode\TranslationHandler\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \BrunosCode\TranslationHandler\TranslationHandler
 */
class TranslationHandler extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \BrunosCode\TranslationHandler\TranslationHandlerService::class;
    }
}
