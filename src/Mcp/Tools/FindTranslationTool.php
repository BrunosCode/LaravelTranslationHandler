<?php

namespace BrunosCode\TranslationHandler\Mcp\Tools;

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
class FindTranslationTool extends Tool
{
    protected string $description = 'Find a specific translation by key and locale in a storage format.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'format' => $schema->string()
                ->description('Storage format to read from. Valid values: '.implode(', ', TranslationOptions::TYPES))
                ->enum(TranslationOptions::TYPES)
                ->required(),
            'key' => $schema->string()
                ->description('The dot-delimited translation key (e.g. "auth.welcome").')
                ->required(),
            'locale' => $schema->string()
                ->description('The locale to look up (e.g. "en", "it").')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $format = $request->get('format');
        $key = $request->get('key');
        $locale = $request->get('locale');

        try {
            $collection = TranslationHandler::get($format);
        } catch (\Throwable $e) {
            return Response::error('Failed to read translations: '.$e->getMessage());
        }

        $translation = $collection->whereKey($key)->whereLocale($locale)->first();

        if (! $translation) {
            return Response::structured([
                'found' => false,
                'key' => $key,
                'locale' => $locale,
                'format' => $format,
            ]);
        }

        return Response::structured([
            'found' => true,
            'key' => $translation->key,
            'locale' => $translation->locale,
            'value' => $translation->value,
            'format' => $format,
        ]);
    }
}
