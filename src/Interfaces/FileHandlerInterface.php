<?php

namespace BrunosCode\TranslationHandler\Interfaces;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

interface FileHandlerInterface
{
    public function __construct(TranslationOptions $options);

    public function get(?string $path = null): TranslationCollection;

    public function put(TranslationCollection $translations, ?string $path = null): int;

    public function delete(?string $path = null): int;
}
