<?php

namespace BrunosCode\TranslationHandler\Mcp\Tools;

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SortTranslationsTool extends Tool
{
    protected string $description = 'Sort translation keys alphabetically in a storage format. Supports php, json, and csv. Optionally restrict to specific locales or key group prefixes.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'format' => $schema->string()
                ->description('Storage format to sort. Valid values: '.implode(', ', TranslationOptions::SORTABLE_TYPES))
                ->enum(TranslationOptions::SORTABLE_TYPES)
                ->required(),
            'locales' => $schema->array()
                ->items($schema->string())
                ->description('Restrict sorting to these locales. Omit to sort all locales.'),
            'groups' => $schema->array()
                ->items($schema->string())
                ->description('Restrict sorting to these key group prefixes (e.g. ["auth", "validation"]). Omit to sort all groups.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $format = $request->get('format');
        $locales = $request->get('locales') ?? [];
        $groups = $request->get('groups') ?? [];

        try {
            $count = TranslationHandler::sortKeys($format, $locales, $groups);
        } catch (\Throwable $e) {
            return Response::error('Failed to sort translations: '.$e->getMessage());
        }

        return Response::text(json_encode([
            'sorted' => $count > 0,
            'count' => $count,
            'format' => $format,
            'locales' => $locales ?: null,
            'groups' => $groups ?: null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
