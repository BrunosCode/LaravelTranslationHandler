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

class SetTranslationTool extends Tool
{
    protected string $description = 'Set or update a single translation value in a storage format.';

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
            'locale' => $schema->string()
                ->description('The locale (e.g. "en", "it").')
                ->required(),
            'value' => $schema->string()
                ->description('The translation value to set.')
                ->required(),
            'force' => $schema->boolean()
                ->description('If true, overwrite the existing value. Defaults to false (skip if already exists).'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $format = $request->get('format');
        $key = $request->get('key');
        $locale = $request->get('locale');
        $value = $request->get('value');
        $force = (bool) ($request->get('force') ?? false);

        try {
            $translation = new Translation($key, $locale, $value);
            $collection = new TranslationCollection([$translation]);
            $count = TranslationHandler::set($collection, $format, force: $force);
        } catch (\Throwable $e) {
            return Response::error('Failed to set translation: '.$e->getMessage());
        }

        return Response::structured([
            'written' => $count > 0,
            'key' => $key,
            'locale' => $locale,
            'value' => $value,
            'format' => $format,
        ]);
    }
}
