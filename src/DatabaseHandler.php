<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Interfaces\DatabaseHandlerInterface;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DatabaseHandler implements DatabaseHandlerInterface
{
    public function __construct(
        private TranslationOptions $options
    ) {}

    public function get(?string $connection = null): TranslationCollection
    {
        $collection = new TranslationCollection;

        $db = $this->getDB($connection);

        $db->table('translation_values')
            ->join('translation_keys', 'translation_keys.id', '=', 'translation_values.translation_key_id')
            ->where(function ($q) {
                foreach ($this->options->fileNames as $fileName) {
                    $q->orWhere('translation_keys.key', 'like', $fileName.'%');
                }
            })
            ->whereIn('translation_values.locale', $this->options->locales)
            ->whereNull('translation_keys.deleted_at')
            ->whereNull('translation_values.deleted_at')
            ->select(
                'translation_keys.key',
                'translation_values.locale',
                'translation_values.value'
            )
            ->get()
            ->each(fn ($translation) => $collection->addTranslation(new Translation(
                $translation->key,
                $translation->locale,
                $translation->value
            )));

        return $collection;
    }

    public function put(TranslationCollection $translations, ?string $connection = null): int
    {
        $db = $this->getDB($connection);

        $counter = 0;

        $db->transaction(function () use ($translations, $db, &$counter) {
            foreach ($this->options->fileNames as $filename) {
                $filteredTranslations = $translations
                    ->whereGroup($filename, $this->options->keyDelimiter);

                // Load the group's keys and values once and share them with
                // every step: each used to re-query the table on its own.
                $dbKeys = $this->getCurrentKeys($db, $filename);
                $dbValues = $this->getCurrentValues($db, $dbKeys);

                $counter += $this->handleUpdate($db, $filteredTranslations, $filename, $dbKeys, $dbValues);

                $counter += $this->handleInsert($db, $filteredTranslations, $filename, $dbKeys);

                $this->handleSoftDelete($db, $filteredTranslations, $filename, $dbKeys, $dbValues);
            }
        });

        return $counter;
    }

    public function getCurrentKeys(Connection $db, ?string $filename = null): Collection
    {
        return $db->table('translation_keys')
            ->when(! empty($filename), fn ($q) => $q->where('key', 'like', $filename.$this->options->keyDelimiter.'%'))
            ->get();
    }

    /**
     * All value rows (including soft-deleted ones) of the given keys.
     */
    public function getCurrentValues(Connection $db, Collection $dbKeys): Collection
    {
        if ($dbKeys->isEmpty()) {
            return new Collection;
        }

        return $db->table('translation_values')
            ->whereIn('translation_key_id', $dbKeys->pluck('id')->all())
            ->get();
    }

    public function handleInsert(Connection $db, TranslationCollection $translations, ?string $filename = null, ?Collection $dbKeys = null): int
    {
        $dbKeys = $dbKeys ?? $this->getCurrentKeys($db, $filename);

        $dbKeyByKey = $dbKeys->keyBy('key');

        $translationToInsert = $translations->filter(function (Translation $translation) use ($dbKeyByKey) {
            return ! $dbKeyByKey->has($translation->key);
        });

        if ($translationToInsert->isEmpty()) {
            return 0;
        }

        $keysToInsert = $translationToInsert
            ->unique('key')
            ->map(fn (Translation $translation) => [
                'key' => $translation->key,
                'updated_at' => now(),
                'created_at' => now(),
                'deleted_at' => null,
            ]);

        $db->table('translation_keys')->insert($keysToInsert->values()->all());

        $insertedKeyIdByKey = $db->table('translation_keys')
            ->whereIn('key', $keysToInsert->pluck('key')->all())
            ->pluck('id', 'key');

        $valuesToInsert = $translationToInsert
            ->map(fn (Translation $translation) => [
                'translation_key_id' => $insertedKeyIdByKey->get($translation->key),
                'value' => $translation->value,
                'locale' => $translation->locale,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->filter(fn (array $translation) => $translation['translation_key_id'] !== null);

        if (! $db->table('translation_values')->insert($valuesToInsert->values()->all())) {
            return 0;
        }

        return $valuesToInsert->count();
    }

    public function handleUpdate(Connection $db, TranslationCollection $translations, ?string $filename = null, ?Collection $dbKeys = null, ?Collection $dbValues = null): int
    {
        $dbKeys = $dbKeys ?? $this->getCurrentKeys($db, $filename);
        $dbValues = $dbValues ?? $this->getCurrentValues($db, $dbKeys);

        $dbKeyByKey = $dbKeys->keyBy('key');
        $dbValueByPair = $dbValues->keyBy(fn ($value) => $value->translation_key_id.'|'.$value->locale);

        $translationToUpdate = $translations->filter(function (Translation $translation) use ($dbKeyByKey) {
            return $dbKeyByKey->has($translation->key);
        });

        $keysToUpdate = $translationToUpdate
            ->unique('key')
            ->filter(function (Translation $translation) use ($dbKeyByKey) {
                $dbKey = $dbKeyByKey->get($translation->key);

                return $dbKey !== null && $dbKey->deleted_at !== null;
            })
            ->map(fn (Translation $translation) => [
                'key' => $translation->key,
                'updated_at' => now(),
                'deleted_at' => null,
            ]);

        if ($keysToUpdate->isNotEmpty()) {
            $db->table('translation_keys')
                ->upsert(
                    $keysToUpdate->values()->all(),
                    ['key'],
                    ['updated_at', 'deleted_at']
                );
        }

        $valuesToUpdate = $translationToUpdate
            ->map(function (Translation $translation) use ($dbKeyByKey, $dbValueByPair) {
                $dbKey = $dbKeyByKey->get($translation->key);
                $dbValue = $dbKey !== null
                    ? $dbValueByPair->get($dbKey->id.'|'.$translation->locale)
                    : null;

                return [
                    'translation_key_id' => $dbKey?->id,
                    'value' => $translation->value,
                    'locale' => $translation->locale,
                    'created_at' => $dbValue->created_at ?? now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                    '_dbValue' => $dbValue,
                ];
            })
            ->filter(fn (array $row) => $row['translation_key_id'] !== null)
            ->filter(function (array $row) {
                $dbValue = $row['_dbValue'];

                if ($dbValue === null) {
                    return true;
                }

                return $dbValue->value !== $row['value'] || $dbValue->deleted_at !== null;
            })
            ->map(function (array $row) {
                unset($row['_dbValue']);

                return $row;
            });

        if ($valuesToUpdate->isEmpty()) {
            return 0;
        }

        return $db->table('translation_values')
            ->upsert(
                $valuesToUpdate->values()->all(),
                ['translation_key_id', 'locale'],
                ['value', 'updated_at', 'created_at', 'deleted_at']
            );
    }

    public function handleSoftDelete(Connection $db, TranslationCollection $translations, ?string $filename = null, ?Collection $dbKeys = null, ?Collection $dbValues = null): int
    {
        $dbKeys = $dbKeys ?? $this->getCurrentKeys($db, $filename);
        $dbValues = $dbValues ?? $this->getCurrentValues($db, $dbKeys);

        // Like the file handlers, this handler only manages the configured
        // locales: values of other locales are never touched, and a key is
        // only retired once no active value of another locale depends on it.
        $managedLocales = array_flip($this->options->locales);

        $dbKeyIdByKey = $dbKeys->pluck('id', 'key');

        $wantedPairs = [];

        foreach ($translations as $translation) {
            $keyId = $dbKeyIdByKey->get($translation->key);

            if ($keyId !== null) {
                $wantedPairs[$keyId.'|'.$translation->locale] = true;
            }
        }

        $valueIdsToSoftDelete = $dbValues
            ->filter(fn ($value) => $value->deleted_at === null
                && isset($managedLocales[$value->locale])
                && ! isset($wantedPairs[$value->translation_key_id.'|'.$value->locale]))
            ->pluck('id')
            ->all();

        if (! empty($valueIdsToSoftDelete)) {
            $db->table('translation_values')
                ->whereIn('id', $valueIdsToSoftDelete)
                ->update(['deleted_at' => now()]);
        }

        $keyIdsWithActiveUnmanagedValues = $dbValues
            ->filter(fn ($value) => $value->deleted_at === null && ! isset($managedLocales[$value->locale]))
            ->pluck('translation_key_id')
            ->flip()
            ->all();

        $newKeys = $translations->map(fn (Translation $translation) => $translation->key)->unique();

        $keysToSoftDelete = $dbKeys
            ->whereNotIn('key', $newKeys)
            ->reject(fn ($dbKey) => isset($keyIdsWithActiveUnmanagedValues[$dbKey->id]));

        if ($keysToSoftDelete->isNotEmpty()) {
            $db->table('translation_keys')
                ->whereIn('id', $keysToSoftDelete->pluck('id')->all())
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);
        }

        return $keysToSoftDelete->count();
    }

    public function delete(?string $connection = null, bool $hardDelete = false): int
    {
        $db = $this->getDB($connection);

        $counter = 0;

        $db->transaction(function () use ($db, &$counter, $hardDelete) {
            foreach ($this->options->fileNames as $filename) {
                $dbKeys = $this->getCurrentKeys($db, $filename);

                $keysQuery = $db->table('translation_keys')
                    ->whereIn('id', $dbKeys->pluck('id')->toArray());

                $valuesQuery = $db->table('translation_values')
                    ->whereIn('translation_key_id', $dbKeys->pluck('id')->toArray());

                if ($hardDelete) {
                    $counter += $valuesQuery->delete();

                    $keysQuery->delete();

                    continue;
                }

                $keysQuery->whereNull('deleted_at')->update(['deleted_at' => now()]);

                $counter += $valuesQuery->whereNull('deleted_at')->update(['deleted_at' => now()]);
            }
        });

        return $counter;
    }

    public function getDB(?string $connection = null): Connection
    {
        return $connection !== null ? DB::connection($connection) : DB::connection();
    }
}
