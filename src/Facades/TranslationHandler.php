<?php

namespace BrunosCode\TranslationHandler\Facades;

use BrunosCode\TranslationHandler\TranslationHandlerService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \BrunosCode\TranslationHandler\Data\Translation|null find(string $from, string $key, string $locale, ?string $path = null)
 * @method static \BrunosCode\TranslationHandler\Collections\TranslationCollection listTranslations(string $from, ?string $path = null, ?string $locale = null, ?string $group = null)
 * @method static \Illuminate\Support\Collection<int, string> listGroups(string $from, ?string $path = null, int $level = 0, ?string $search = null)
 * @method static false|int sync(string $from, string $to, bool $force = false, ?string $fromPath = null, ?string $toPath = null)
 * @method static false|int import(?string $from = null, ?string $to = null, bool $force = false, ?string $fromPath = null, ?string $toPath = null)
 * @method static false|int export(?string $from = null, ?string $to = null, bool $force = false, ?string $fromPath = null, ?string $toPath = null)
 * @method static \BrunosCode\TranslationHandler\Collections\TranslationCollection get(string $from, ?string $path = null)
 * @method static int set(\BrunosCode\TranslationHandler\Collections\TranslationCollection $translations, string $to, ?string $path = null, bool $force = false)
 * @method static int sortKeys(string $from, array $locales = [], array $groups = [], ?string $path = null)
 * @method static int deleteKey(string $from, string $key, ?string $locale = null, ?string $path = null)
 * @method static int deleteGroup(string $from, string $group, ?string $path = null)
 * @method static int delete(string $from, ?string $path = null)
 * @method static array check(string $from, array $locales, ?array $sides = null, ?string $fromPath = null, bool $includeOrphans = false)
 * @method static \BrunosCode\TranslationHandler\TranslationChecker getChecker()
 * @method static array getTypes()
 * @method static \BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface getPhpHandler()
 * @method static \BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface getCsvHandler()
 * @method static \BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface getJsonHandler()
 * @method static \BrunosCode\TranslationHandler\Interfaces\DatabaseHandlerInterface getDbHandler()
 * @method static \BrunosCode\TranslationHandler\Data\TranslationOptions getOptions()
 * @method static \BrunosCode\TranslationHandler\TranslationHandlerService setOptions(\BrunosCode\TranslationHandler\Data\TranslationOptions $options)
 * @method static \BrunosCode\TranslationHandler\TranslationHandlerService resetOptions()
 * @method static \BrunosCode\TranslationHandler\TranslationHandlerService setOption(string $name, mixed $value)
 * @method static mixed getOption(string $name)
 * @method static \BrunosCode\TranslationHandler\Data\TranslationOptions getDefaultOptions()
 * @method static \BrunosCode\TranslationHandler\TranslationHandlerService setDefaultOptions(\BrunosCode\TranslationHandler\Data\TranslationOptions $options)
 * @method static \BrunosCode\TranslationHandler\TranslationHandlerService setDefaultOption(string $name, mixed $value)
 * @method static mixed getDefaultOption(string $name)
 *
 * @see TranslationHandlerService
 */
class TranslationHandler extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TranslationHandlerService::class;
    }
}
