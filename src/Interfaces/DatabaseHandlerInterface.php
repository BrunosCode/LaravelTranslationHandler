<?php

namespace BrunosCode\TranslationHandler\Interfaces;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\TranslationOptions;

interface DatabaseHandlerInterface
{
    public function __construct(TranslationOptions $options);

    public function get(?string $connection = null): TranslationCollection;

    public function put(TranslationCollection $translations, ?string $connection = null): int;

    public function delete(?string $connection = null, bool $hardDelete = false): int;
}
