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
class ListTranslationsTool extends Tool
{
    protected string $description = 'List translations from a storage format. Optionally filter by locale or key group prefix.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'format' => $schema->string()
                ->description('Storage format to read from. Valid values: '.implode(', ', TranslationOptions::TYPES))
                ->enum(TranslationOptions::TYPES)
                ->required(),
            'locale' => $schema->string()
                ->description('Filter by locale (e.g. "en", "it"). Omit to get all locales.'),
            'group' => $schema->string()
                ->description('Filter by key group prefix (e.g. "auth" returns all keys starting with "auth."). Omit to get all keys.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $format = $request->get('format');
        $locale = $request->get('locale');
        $group = $request->get('group');

        try {
            $collection = TranslationHandler::get($format);
        } catch (\Throwable $e) {
            return Response::error('Failed to read translations: '.$e->getMessage());
        }

        if ($locale) {
            $collection = $collection->whereLocale($locale);
        }

        if ($group) {
            $collection = $collection->whereGroup($group);
        }

        $translations = $collection
            ->map(fn ($t) => ['key' => $t->key, 'locale' => $t->locale, 'value' => $t->value])
            ->values()
            ->all();

        return Response::structured([
            'format' => $format,
            'total' => count($translations),
            'translations' => $translations,
        ]);
    }
}
