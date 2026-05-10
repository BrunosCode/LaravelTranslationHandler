<?php

namespace BrunosCode\TranslationHandler\Mcp\Tools;

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteTranslationTool extends Tool
{
    protected string $description = 'Delete a translation key from a storage format. Optionally restrict deletion to a specific locale; omit locale to delete all locales for the key.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'format' => $schema->string()
                ->description('Storage format to delete from. Valid values: '.implode(', ', TranslationOptions::TYPES))
                ->enum(TranslationOptions::TYPES)
                ->required(),
            'key' => $schema->string()
                ->description('The dot-delimited translation key to delete (e.g. "auth.welcome").')
                ->required(),
            'locale' => $schema->string()
                ->description('Delete only this locale. Omit to delete the key for all locales.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $format = $request->get('format');
        $key = $request->get('key');
        $locale = $request->get('locale');

        try {
            $count = TranslationHandler::deleteKey($format, $key, $locale);
        } catch (\Throwable $e) {
            return Response::error('Failed to delete translation: '.$e->getMessage());
        }

        return Response::text(json_encode([
            'deleted' => $count > 0,
            'count' => $count,
            'key' => $key,
            'locale' => $locale,
            'format' => $format,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
