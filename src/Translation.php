<?php

namespace BrunosCode\TranslationHandler;

use Illuminate\Support\Facades\Validator;

class Translation
{
    public function __construct(
        public string $key,
        public string $locale,
        public string $value
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'locale' => $this->locale,
            'value' => $this->value,
        ];
    }

    public static function validate(array $data): array
    {
        return Validator::make($data, [
            'key' => 'string|required',
            'locale' => 'string|required',
            'value' => 'string|required',
        ])->validate();
    }

    public static function validateArray(array $data): array
    {
        return Validator::make($data, [
            '*.key' => 'string|required',
            '*.locale' => 'string|required',
            '*.value' => 'string|required',
        ])->validate();
    }
}
