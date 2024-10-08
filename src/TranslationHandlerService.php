<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Interfaces\DbHandlerInterface;
use BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface;

class TranslationHandlerService
{
  const PHP = 'php_file';
  const CSV = 'csv_file';
  const JSON = 'json_file';
  const DB = 'db';

  const TYPES = [
    self::PHP,
    self::CSV,
    self::JSON,
    self::DB,
  ];

  public array $fileNames;

  public array $locales;

  public string $defaultExportFrom;

  public string $defaultExportTo;

  public string $defaultImportFrom;

  public string $defaultImportTo;

  public function __construct(
    private FileHandlerInterface $phpHandler,
    private FileHandlerInterface $csvHandler,
    private FileHandlerInterface $jsonHandler,
    private DbHandlerInterface $dbHandler
  ) {
    $this->fileNames = config('translation-handler.file_names', []);
    if (empty($this->fileNames)) {
      throw new \InvalidArgumentException('config translation-handler.file_names cannot be empty');
    }

    $this->locales = config('translation-handler.locales', []);
    if (empty($this->locales)) {
      throw new \InvalidArgumentException('config translation-handler.locales cannot be empty');
    }

    $this->defaultExportFrom = config('translation-handler.default.export_from', self::DB);
    if (!in_array($this->defaultExportFrom, self::TYPES)) {
      throw new \InvalidArgumentException('Invalid default export type');
    }

    $this->defaultExportTo = config('translation-handler.default.export_to', self::PHP);
    if (!in_array($this->defaultExportTo, self::TYPES)) {
      throw new \InvalidArgumentException('Invalid default export to type');
    }

    $this->defaultImportFrom = config('translation-handler.default.import_from', self::PHP);
    if (!in_array($this->defaultImportFrom, self::TYPES)) {
      throw new \InvalidArgumentException('Invalid default import from type');
    }

    $this->defaultImportTo = config('translation-handler.default.import_to', self::DB);
    if (!in_array($this->defaultImportTo, self::TYPES)) {
      throw new \InvalidArgumentException('Invalid default import to type');
    }
  }

  public function import(?string $from = null, ?string $to = null, ?array $fileNames = null, ?array $locales = null): bool
  {
    $from = $from ?? $this->defaultImportFrom;
    if (!is_string($from) || !in_array($from, self::TYPES)) {
      throw new \InvalidArgumentException('Invalid import type');
    }

    $to = $to ?? $this->defaultExportTo;
    if (!is_string($to) || !in_array($to, self::TYPES)) {
      throw new \InvalidArgumentException('Invalid export type');
    }

    $fileNames = $fileNames ?? $this->fileNames;
    $locales = $locales ?? $this->locales;

    $fromHandler = match ($from) {
      self::PHP => $this->phpHandler,
      self::CSV => $this->csvHandler,
      self::JSON => $this->jsonHandler,
      self::DB => $this->dbHandler,
    };

    $toHandler = match ($to) {
      self::PHP => $this->phpHandler,
      self::CSV => $this->csvHandler,
      self::JSON => $this->jsonHandler,
      self::DB => $this->dbHandler,
    };

    $translations = $fromHandler->get($fileNames, $locales);
    return (bool) $toHandler->store($translations, $fileNames, $locales);
  }

  public function export(?string $from = null, ?string $to = null, ?array $fileNames = null, ?array $locales = null): bool
  {
    $from = $from ?? $this->defaultImportFrom;
    if (!is_string($from) || !in_array($from, self::TYPES)) {
      throw new \InvalidArgumentException('Invalid import type');
    }

    $to = $to ?? $this->defaultExportTo;
    if (!is_string($to) || !in_array($to, self::TYPES)) {
      throw new \InvalidArgumentException('Invalid export type');
    }

    $fileNames = $fileNames ?? $this->fileNames;
    $locales = $locales ?? $this->locales;

    $fromHandler = match ($from) {
      self::PHP => $this->phpHandler,
      self::CSV => $this->csvHandler,
      self::JSON => $this->jsonHandler,
      self::DB => $this->dbHandler,
    };

    $toHandler = match ($to) {
      self::PHP => $this->phpHandler,
      self::CSV => $this->csvHandler,
      self::JSON => $this->jsonHandler,
      self::DB => $this->dbHandler,
    };

    $translations = $fromHandler->get($fileNames, $locales);
    return (bool) $toHandler->store($translations, $fileNames, $locales);
  }

  public function getTypes(): array
  {
    return self::TYPES;
  }

  public function getDefaultExportFrom(): string
  {
    return $this->defaultExportFrom;
  }

  public function getDefaultExportTo(): string
  {
    return $this->defaultExportTo;
  }

  public function getDefaultImportFrom(): string
  {
    return $this->defaultImportFrom;
  }

  public function getDefaultImportTo(): string
  {
    return $this->defaultImportTo;
  }

  public function getFileNames(): array
  {
    return $this->fileNames;
  }

  public function getLocales(): array
  {
    return $this->locales;
  }
}
