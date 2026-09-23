<?php

namespace BrunosCode\TranslationHandler\Data;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;

class Translation
{
    public string $key;

    public string $locale;

    public ?string $value;

    public ?string $keyId = null;

    public function __construct(
        string $key,
        string $locale,
        ?string $value
    ) {
        // Plain checks mirroring validator(): a Validator instance per object
        // is the dominant cost when thousands of translations are loaded.
        if ($key === '') {
            throw new \InvalidArgumentException('Translation key is required');
        }

        if ($locale === '') {
            throw new \InvalidArgumentException('Translation locale is required');
        }

        if (mb_strlen($locale) < 2) {
            throw new \InvalidArgumentException("Translation locale \"{$locale}\" is too short (min 2 characters)");
        }

        if (mb_strlen($locale) > 7) {
            throw new \InvalidArgumentException("Translation locale \"{$locale}\" is too long (max 7 characters)");
        }

        $this->key = $key;
        $this->locale = $locale;
        $this->value = $value;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'locale' => $this->locale,
            'value' => $this->value,
        ];
    }

    public static function validator(array $data): ValidatorContract
    {
        return Validator::make($data, [
            'key' => 'string|required|min:1',
            'locale' => 'string|required|min:2|max:7',
            'value' => 'string|nullable',
        ], [
            'key.required' => 'Translation key is required',
            'key.min' => 'Translation key cannot be empty',
            'locale.required' => 'Translation locale is required',
            'locale.min' => 'Translation locale ":input" is too short (min 2 characters)',
            'locale.max' => 'Translation locale ":input" is too long (max 7 characters)',
        ]);
    }

    public static function arrayValidator(array $data): ValidatorContract
    {
        return Validator::make($data, [
            '*.key' => 'string|required|min:1',
            '*.locale' => 'string|required|min:2|max:7',
            '*.value' => 'string|nullable',
        ]);
    }

    public static function fake(?TranslationOptions $options = null): self
    {
        $options ??= new TranslationOptions;

        $filename = fake()->randomElement($options->fileNames);
        $key = $filename.$options->keyDelimiter.str(fake()->slug())->replace('-', $options->keyDelimiter)->toString();

        return new self(
            $key,
            fake()->randomElement($options->locales),
            fake()->sentence(),
        );
    }
}
