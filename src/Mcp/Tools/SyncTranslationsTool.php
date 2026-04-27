<?php

namespace BrunosCode\TranslationHandler\Mcp\Tools;

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class SyncTranslationsTool extends Tool
{
    protected string $description = 'Move or copy translations from one storage format to another.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'from' => $schema->string()
                ->description('Source storage format. Valid values: '.implode(', ', TranslationOptions::TYPES))
                ->enum(TranslationOptions::TYPES)
                ->required(),
            'to' => $schema->string()
                ->description('Destination storage format. Valid values: '.implode(', ', TranslationOptions::TYPES))
                ->enum(TranslationOptions::TYPES)
                ->required(),
            'force' => $schema->boolean()
                ->description('If true, overwrite existing translations in the destination. Defaults to false.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $force = (bool) ($request->get('force') ?? false);

        if ($from === $to) {
            return Response::error('Source and destination formats must be different.');
        }

        try {
            $success = TranslationHandler::import($from, $to, force: $force);
        } catch (\Throwable $e) {
            return Response::error('Failed to sync translations: '.$e->getMessage());
        }

        return Response::structured([
            'success' => $success,
            'from' => $from,
            'to' => $to,
            'force' => $force,
        ]);
    }
}
