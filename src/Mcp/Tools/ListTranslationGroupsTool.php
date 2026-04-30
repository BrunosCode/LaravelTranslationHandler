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
class ListTranslationGroupsTool extends Tool
{
    protected string $description = 'List unique translation key groups from a storage format. A group is a key prefix at a given depth level (defined by the number of key delimiters). Optionally filter by search string.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'format' => $schema->string()
                ->description('Storage format to read from. Valid values: '.implode(', ', TranslationOptions::TYPES))
                ->enum(TranslationOptions::TYPES)
                ->required(),
            'level' => $schema->integer()
                ->description('Number of delimiters in the group name. 0 = top-level groups (e.g. "auth"), 1 = second-level groups (e.g. "auth.messages"). Defaults to 0.'),
            'search' => $schema->string()
                ->description('Filter groups whose name contains this string (case-insensitive).'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $format = $request->get('format');
        $level = (int) ($request->get('level') ?? 0);
        $search = $request->get('search');

        try {
            $collection = TranslationHandler::get($format);
        } catch (\Throwable $e) {
            return Response::error('Failed to read translations: '.$e->getMessage());
        }

        $delimiter = TranslationHandler::getOption('keyDelimiter') ?? '.';
        $depth = $level + 1;

        $groups = $collection
            ->map(fn ($t) => $t->key)
            ->unique()
            ->map(function ($key) use ($delimiter, $depth) {
                $segments = explode($delimiter, $key);

                if (count($segments) <= $depth) {
                    return null;
                }

                return implode($delimiter, array_slice($segments, 0, $depth));
            })
            ->filter()
            ->unique()
            ->when($search, fn ($items) => $items->filter(
                fn ($group) => str_contains(strtolower($group), strtolower($search))
            ))
            ->sort()
            ->values()
            ->all();

        return Response::structured([
            'format' => $format,
            'level' => $level,
            'total' => count($groups),
            'groups' => $groups,
        ]);
    }
}
