<?php

namespace BrunosCode\TranslationHandler\Concerns;

trait ComparesTranslations
{
    protected function rawTranslationsEqual(array $a, array $b): bool
    {
        return json_encode($a) === json_encode($b);
    }

    protected function countRawDifferences(array $existing, array $new): int
    {
        $flatExisting = $this->flattenForComparison($existing);
        $flatNew = $this->flattenForComparison($new);

        $diff = 0;

        foreach ($flatNew as $key => $value) {
            if (! array_key_exists($key, $flatExisting) || $flatExisting[$key] !== $value) {
                $diff++;
            }
        }

        foreach ($flatExisting as $key => $value) {
            if (! array_key_exists($key, $flatNew)) {
                $diff++;
            }
        }

        return $diff;
    }

    private function flattenForComparison(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $result += $this->flattenForComparison($value, $newKey);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
