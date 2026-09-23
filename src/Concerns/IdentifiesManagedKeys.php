<?php

namespace BrunosCode\TranslationHandler\Concerns;

trait IdentifiesManagedKeys
{
    /**
     * Whether a key stored in a shared file (flat JSON, CSV) belongs to one of
     * the groups this handler manages, i.e. starts with a configured file name
     * followed by the key delimiter. Anything else in the file is left as is.
     */
    protected function isManagedKey(string $key): bool
    {
        foreach ($this->options->fileNames as $fileName) {
            if (str_starts_with($key, $fileName.$this->options->keyDelimiter)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a top-level key of a nested file (nested JSON) is one of the
     * groups this handler manages.
     */
    protected function isManagedGroup(string $group): bool
    {
        return in_array($group, $this->options->fileNames, true);
    }
}
