<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Interfaces\FileHandlerInterface;
use Illuminate\Support\Facades\File;

class PhpFileHandler implements FileHandlerInterface
{
  private string $defaultKeySeparator;

  private bool $formatPhpExport;

  public function __construct()
  {
    $this->defaultKeySeparator = config('translation-handler.key_separator', '.');
    $this->formatPhpExport = config('translation-handler.format_php_export', false);
  }

  /**
   * Retrieves translations from php files.
   *
   * @param array<string> $fileNames a list of filenames to retrieve. If null, the default list from the config is used.
   * @param array<string> $locales a list of locales to retrieve. If null, the default list from the config is used.
   * @return array<Translation> the list of translations
   * @throws \InvalidArgumentException if the filename or locale is empty.
   */
  public function get(array $fileNames, array $locales): array
  {
    $translations = [];

    foreach ($fileNames as $filename) {
      if (empty($filename)) {
        throw new \InvalidArgumentException('Filename cannot be empty');
      }

      foreach ($locales as $locale) {
        if (empty($locale)) {
          throw new \InvalidArgumentException('Locale cannot be empty');
        }
        $rawTranslations = $this->read($filename, $locale);
        $translations = $this->build($translations, $filename, $locale, $rawTranslations);
      }
    }

    return $translations;
  }

  /**
   * Recursively builds a list of Translations from a nested array.
   *
   * @param array<Translation> $translations the list of translations to append to
   * @param string $key the key of the current translation
   * @param string $locale the locale of the current translation
   * @param string|array $value the value of the current key
   *
   * @return array<Translation> the list of translations with the new ones appended
   */
  private function build(array $translations, string $key, string $locale, string|array &$value): array
  {
    if (is_array($value)) {
      foreach ($value as $childKey => $childValue) {
        $currentKey = $key ? $key . $this->defaultKeySeparator . $childKey : $childKey;

        $translations = $this->build($translations, $currentKey, $locale, $childValue);
      }
    } else {
      $translations[] = new Translation($key, $locale, $value);
    }

    return $translations;
  }

  /**
   * Retrieves the translations from a php file.
   *
   * @param string $filename the name of the file to read.
   * @param string $locale the locale of the translations to read.
   * @return array the list of translations in the file.
   * @throws \InvalidArgumentException if the filename or locale is empty.
   */
  protected function read(string $filename, string $locale): array
  {
    if (empty($filename)) {
      throw new \InvalidArgumentException('Filename cannot be empty');
    }

    if (empty($locale)) {
      throw new \InvalidArgumentException('Locale cannot be empty');
    }

    $path = lang_path("{$locale}/{$filename}.php");

    if (! File::exists($path)) {
      return [];
    }

    $rawTranslations = include $path;

    if (! is_array($rawTranslations)) {
      return [];
    }

    return $rawTranslations;
  }

  /**
   * Writes the translations to their respective files.
   *
   * @param array<Translation> $translations the list of translations to write
   * @param array<string> $fileNames the list of filenames to write to. If null, the default filenames will be used.
   * @param array<string> $locales the list of locales to write to. If null, the default locales will be used.
   * @return string the path to the language files
   * @throws \InvalidArgumentException if the filename or locale is empty.
   */
  public function store(array $translations, array $fileNames, array $locales, bool $force = false): string
  {
    foreach ($fileNames as $filename) {
      if (empty($filename)) {
        throw new \InvalidArgumentException('Filename cannot be empty');
      }

      foreach ($locales as $locale) {
        if (empty($locale)) {
          throw new \InvalidArgumentException('Locale cannot be empty');
        }

        $buildedTranslations = $this->buildForFile($translations, $filename, $locale);
        $this->write($buildedTranslations, $filename, $locale, $force);
      }
    }

    return lang_path();
  }

  /**
   * Builds a nested array of translations for a single file.
   *
   * The filename is stripped from the key if it is not empty.
   *
   * @param array<Translation> $translations the list of translations to build into the file
   * @param string $filename the name of the file to build
   * @param string $locale the locale of the translations in the file
   * @return array the nested array of translations
   */
  protected function buildForFile(array $translations, string $filename, string $locale): array
  {
    $fileTranslations = [];

    foreach ($translations as $translation) {
      if ($translation->locale != $locale) {
        continue;
      }
      
      $keys = explode($this->defaultKeySeparator, $translation->key);

      if (!empty($filename) && $keys[0] == $filename) {
        unset($keys[0]);
      }

      $current = &$fileTranslations;

      foreach ($keys as $key) {
        if (!isset($current[$key])) {
          $current[$key] = [];
        }
        $current = &$current[$key];
      }

      $current = $translation->value;
    }
    return $fileTranslations;
  }

  /**
   * Writes the translations to the file.
   *
   * If the file does not exist, the folder is created recursively first.
   *
   * @param array $translations the list of translations to write
   * @param string $filename the name of the file to write to
   * @param string $locale the locale of the translations to write
   * @return int|bool the number of bytes written or false if the file could not be written
   */
  protected function write(array $translations, string $filename, string $locale, bool $force = false): int|bool
  {
    if (!$force) {
      $currentTranslations = $this->read($filename, $locale);
  
      $translations = array_replace_recursive($currentTranslations, $translations);
    }

    $path = lang_path("test/{$locale}/{$filename}.php");

    // check if folder exists
    if (! File::exists(dirname($path))) {
      File::makeDirectory(dirname($path), 0777, true);
    }

    return File::put(
      $path,
      '<?php return ' . $this->exportPhp($translations) . ';',
      0664
    );
  }


  /**
   * Formats the given array of translations into a string that can be used in a php file.
   *
   * If the config option `format_php_export` is set to false, the output of var_export is returned directly.
   * Otherwise, the output is formatted to be more readable.
   *
   * The formatting is done by replacing "array (" with "[", and ")\n" with "]\n".
   *
   * @param array $translations the list of translations to format
   * @return string the formatted string
   */
  protected function exportPhp(array $translations): string
  {
    $export = var_export($translations, true);
    if (!$this->formatPhpExport) {
      return $export;
    }

    $patterns = [
      "/array \(/" => '[',
      "/^([ ]*)\)(,?)$/m" => '$1]$2',
    ];
    $output = preg_replace(array_keys($patterns), array_values($patterns), $export);
    
    return $output;
  }
}
