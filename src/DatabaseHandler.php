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
                    ->whereGroup($filename);

                $dbKeys = $this->getCurrentKeys($db, $filename);

                $counter += $this->handleUpdate($db, $filteredTranslations, $filename, $dbKeys);

                $counter += $this->handleInsert($db, $filteredTranslations, $filename, $dbKeys);

                $this->handleSoftDelete($db, $filteredTranslations, $filename, $dbKeys);
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

    public function handleInsert(Connection $db, TranslationCollection $translations, ?string $filename = null, ?Collection $dbKeys = null): int
    {
        $dbKeys = $dbKeys ?? $this->getCurrentKeys($db, $filename);

        $translationToInsert = $translations->filter(function (Translation $translation) use ($dbKeys) {
            return ! $dbKeys->contains('key', $translation->key);
        });

        $keysToInsert = $translationToInsert
            ->unique('key')
            ->map(fn (Translation $translation) => [
                'key' => $translation->key,
                'updated_at' => now(),
                'created_at' => now(),
                'deleted_at' => null,
            ]);

        $insertedKeys = $db->table('translation_keys')->insert($keysToInsert->toArray());

        if ($insertedKeys < 0) {
            return 0;
        }

        $dbKeys = $this->getCurrentKeys($db, $filename);

        $valuesToInsert = $translationToInsert
            ->map(fn (Translation $translation) => [
                'translation_key_id' => $dbKeys->where('key', $translation->key)->first()?->id,
                'value' => $translation->value,
                'locale' => $translation->locale,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->filter(fn (array $translation) => $translation['translation_key_id'] !== null);

        if (! $db->table('translation_values')->insert($valuesToInsert->toArray())) {
            return 0;
        }

        return $valuesToInsert->count();
    }

    public function handleUpdate(Connection $db, TranslationCollection $translations, ?string $filename = null, ?Collection $dbKeys = null): int
    {
        $dbKeys = $dbKeys ?? $this->getCurrentKeys($db, $filename);

        $translationToInsert = $translations->filter(function (Translation $translation) use ($dbKeys) {
            return $dbKeys->contains('key', $translation->key);
        });

        $keysToUpdate = $translationToInsert
            ->unique('key')
            ->map(fn (Translation $translation) => [
                'key' => $translation->key,
                'updated_at' => now(),
                'deleted_at' => null,
            ]);

        $updatedKeys = $db->table('translation_keys')
            ->upsert(
                $keysToUpdate->toArray(),
                ['key'],
                ['updated_at', 'deleted_at']
            );

        if ($updatedKeys < 0) {
            return 0;
        }

        $dbValues = $db->table('translation_values')
            ->whereIn('translation_key_id', $dbKeys->pluck('id')->toArray())
            ->get();

        $valuesToUpdate = $translationToInsert
            ->map(function (Translation $translation) use ($dbKeys, $dbValues) {
                $dbKey = $dbKeys->where('key', $translation->key)->first();
                $dbValue = $dbKey !== null
                    ? $dbValues->where('translation_key_id', $dbKey->id)
                        ->where('locale', $translation->locale)
                        ->first()
                    : null;

                return [
                    'translation_key_id' => $dbKey?->id,
                    'value' => $translation->value,
                    'locale' => $translation->locale,
                    'created_at' => $dbValue?->created_at ?? now(),
                    'updated_at' => now(),
                ];
            })
            ->filter(fn (array $translation) => $translation['translation_key_id'] !== null);

        return $db->table('translation_values')
            ->upsert(
                $valuesToUpdate->toArray(),
                ['translation_key_id', 'locale'],
                ['value', 'updated_at', 'created_at']
            );
    }

    public function handleSoftDelete(Connection $db, TranslationCollection $translations, ?string $filename = null, ?Collection $dbKeys = null): int
    {
        $dbKeys = $dbKeys ?? $this->getCurrentKeys($db, $filename);

        $keysToSoftDelete = $dbKeys->whereNotIn('key', $translations->map(fn (Translation $translation) => $translation->key));

        $db->table('translation_keys')
            ->whereIn('id', $keysToSoftDelete->pluck('id')->toArray())
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        return $db->table('translation_values')
            ->whereIn('translation_key_id', $keysToSoftDelete->pluck('id')->toArray())
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);
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
