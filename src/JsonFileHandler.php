<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface;

class JsonFileHandler implements FileHandlerInterface
{
  // TODO: implement
  public function get(array $fileNames, array $locales): array
  {
    return [];
  }

  public function store(array $translations, array $fileNames, array $locales, bool $force = false): string
  {
    return '';
  }
}
