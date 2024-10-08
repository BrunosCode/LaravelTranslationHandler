<?php

namespace BrunosCode\TranslationHandler;

use BrunosCode\TranslationHandler\Interfaces\DbHandlerInterface;
use Illuminate\Support\Facades\DB;

class DbHandler implements DbHandlerInterface
{
    private string $keyTable;

    private string $valueTable;

    public function __construct()
    {
        $this->keyTable = config('translation-handler.key_table_name', 'translation_keys');
        $this->valueTable = config('translation-handler.value_table_name', 'translation_values');
    }

    public function get(array $fileNames, array $locales): array
    {
        return DB::table('translation_values')
            ->join('translation_keys', 'translation_keys.id', '=', 'translation_values.translation_key_id')
            ->when(! empty($fileNames), function ($q) use ($fileNames) {
                $q->whereHas('translation_key', function ($q) use ($fileNames) {
                    foreach ($fileNames as $fileName) {
                        $q->orWhere('file_name', $fileName);
                    }
                });
            })
            ->when(! empty($locales), function ($q) use ($locales) {
                $q->whereIn('locale', $locales);
            })
            ->get()
            ->select(
                'translation_keys.key',
                'translation_values.locale',
                'translation_values.value'
            )
            ->map(fn ($translation) => new Translation(
                $translation->key,
                $translation->locale,
                $translation->value
            ))
            ->toArray();
    }

    public function store(array $translations, array $fileNames, array $locales, bool $force = false): int
    {
        $keys = [];
        $counter = 0;

        foreach ($translations as $translation) {
            if ($translation->key == '' || $translation->locale == '' || $translation->value == '') {
                continue;
            }

            if (! isset($keys[$translation->key])) {
                $keyId = DB::table($this->keyTable)
                    ->firstOrCreate([
                        'key' => $translation->key,
                    ])->id;

                $keys[$translation->key] = $keyId;
            } else {
                $keyId = $keys[$translation->key];
            }

            if ($force) {
                $bool = DB::table($this->valueTable)->updateOrInsert([
                    'translation_key_id' => $keyId,
                    'locale' => $translation->locale,
                ], [
                    'value' => $translation->value,
                ]);
            } else {
                $bool = DB::table($this->valueTable)->where([
                    'translation_key_id' => $keyId,
                    'locale' => $translation->locale,
                ])->update([
                    'value' => $translation->value,
                ]);
            }

            if ($bool) {
                $counter++;
            }
        }

        return $counter;
    }
}
