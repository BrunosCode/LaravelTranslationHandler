<?php

namespace BrunosCode\TranslationHandler\Mcp\Tools;

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteTranslationGroupTool extends Tool
{
    protected string $description = 'Delete all translations under a key group prefix from a storage format. All keys starting with the group prefix are removed.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'format' => $schema->string()
                ->description('Storage format to delete from. Valid values: '.implode(', ', TranslationOptions::TYPES))
                ->enum(TranslationOptions::TYPES)
                ->required(),
            'group' => $schema->string()
                ->description('The key group prefix to delete (e.g. "auth" removes all keys starting with "auth.").')
                ->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $format = $request->get('format');
        $group = $request->get('group');

        try {
            $count = TranslationHandler::deleteGroup($format, $group);
        } catch (\Throwable $e) {
            return Response::error('Failed to delete translation group: '.$e->getMessage());
        }

        return Response::text(json_encode([
            'deleted' => $count > 0,
            'count' => $count,
            'group' => $group,
            'format' => $format,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
