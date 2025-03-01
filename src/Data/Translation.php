<?php

namespace BrunosCode\TranslationHandler\Data;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;

class Translation
{
    public string $key;

    public string $locale;

    public string $value;

    public ?string $keyId = null;

    public function __construct(
        string $key,
        string $locale,
        string $value
    ) {
        $validator = self::validator([
            'key' => $key,
            'locale' => $locale,
            'value' => $value,
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        $validated = $validator->validated();

        $this->key = $validated['key'];
        $this->locale = $validated['locale'];
        $this->value = $validated['value'];
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

        $filename = fake()->randomElement($options->phpFileNames);
        $key = $filename.$options->keyDelimiter.str(fake()->slug())->replace('-', $options->keyDelimiter)->toString();

        return new self(
            $key,
            fake()->randomElement($options->locales),
            fake()->sentence(),
        );
    }
}
