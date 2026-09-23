<?php

namespace BrunosCode\TranslationHandler\Concerns;

trait NormalizesRawValues
{
    /**
     * Coerce a leaf read from a lang source (PHP array, decoded JSON, DB row)
     * into the string|null a Translation accepts. Scalars are cast, null is
     * kept (meaning "not translated"), anything else is rejected with the
     * offending key in the message instead of surfacing as a TypeError.
     */
    protected function normalizeRawValue(mixed $value, string $key): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        throw new \InvalidArgumentException(
            "Translation value for key \"{$key}\" must be a string, number, boolean or null, ".get_debug_type($value).' given'
        );
    }
}
