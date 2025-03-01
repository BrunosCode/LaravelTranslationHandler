<?php

namespace BrunosCode\TranslationHandler\Interfaces;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

interface FileHandlerInterface
{
    public function __construct(TranslationOptions $options);

    public function get(?string $path = null, null|string|array $fileNames = null): TranslationCollection;

    public function put(TranslationCollection $translations, ?string $path = null, null|string|array $fileNames = null): int;

    public function delete(?string $path = null, null|string|array $fileNames = null): int;
}
