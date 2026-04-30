<?php

namespace BrunosCode\TranslationHandler\Mcp\Tools;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class SetAllLocalesTranslationTool extends Tool
{
    protected string $description = 'Set or update a translation key for all locales at once in a storage format.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'format' => $schema->string()
                ->description('Storage format to write to. Valid values: '.implode(', ', TranslationOptions::TYPES))
                ->enum(TranslationOptions::TYPES)
                ->required(),
            'key' => $schema->string()
                ->description('The dot-delimited translation key (e.g. "auth.welcome").')
                ->required(),
            'values' => $schema->object()
                ->description('Map of locale to translation value (e.g. {"en": "Hello", "it": "Ciao"}).')
                ->required(),
            'force' => $schema->boolean()
                ->description('If true, overwrite existing values. Defaults to false (skip if already exists).'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $format = $request->get('format');
        $key = $request->get('key');
        $values = $request->get('values');
        $force = (bool) ($request->get('force') ?? false);

        if (! is_array($values) || empty($values)) {
            return Response::error('The "values" parameter must be a non-empty object mapping locales to translation values.');
        }

        try {
            $collection = new TranslationCollection(
                array_map(
                    fn ($locale, $value) => new Translation($key, $locale, $value),
                    array_keys($values),
                    array_values($values)
                )
            );

            $count = TranslationHandler::set($collection, $format, force: $force);
        } catch (\Throwable $e) {
            return Response::error('Failed to set translations: '.$e->getMessage());
        }

        return Response::structured([
            'written' => $count,
            'key' => $key,
            'locales' => array_keys($values),
            'format' => $format,
        ]);
    }
}
