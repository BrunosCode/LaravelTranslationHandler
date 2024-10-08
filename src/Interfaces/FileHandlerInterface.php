<?php

namespace BrunosCode\TranslationHandler\Interfaces;

interface FileHandlerInterface
{
    public function get(array $fileNames, array $locales): array;

    public function store(array $translations, array $fileNames, array $locales, bool $force = false): string;
}
