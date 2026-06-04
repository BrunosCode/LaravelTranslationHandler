<?php

namespace BrunosCode\TranslationHandler\Mcp\Tools;

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
class CheckTranslationsTool extends Tool
{
    protected string $description = 'Scan backend PHP and frontend JS/TS source for translation usages and report keys referenced in code but undefined per locale. Optionally also report orphan keys (defined but never referenced). Defined keys are read from the given format, scoped to the configured fileNames.';

    public function schema(JsonSchema $schema): array
    {
        $sides = array_keys(TranslationHandler::getOption('check'));

        return [
            'format' => $schema->string()
                ->description('Storage format to read defined keys from. Valid values: '.implode(', ', TranslationOptions::TYPES))
                ->enum(TranslationOptions::TYPES)
                ->required(),
            'locales' => $schema->array()
                ->items($schema->string())
                ->description('Restrict the report to these locales. Omit to use the configured locales.'),
            'side' => $schema->string()
                ->description('Limit scanning to a single side. Valid values: '.implode(', ', $sides).'. Omit to scan all sides.')
                ->enum($sides),
            'orphans' => $schema->boolean()
                ->description('Also report keys that are defined but never referenced in code. Defaults to false.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $format = $request->get('format');
        $locales = $request->get('locales') ?: TranslationHandler::getOption('locales');
        $side = $request->get('side');
        $sides = $side !== null ? [$side] : null;
        $orphans = (bool) $request->get('orphans');

        if (empty($locales)) {
            return Response::error('No locales configured.');
        }

        try {
            $report = TranslationHandler::check(
                from: $format,
                locales: $locales,
                sides: $sides,
                includeOrphans: $orphans,
            );
        } catch (\Throwable $e) {
            return Response::error('Failed to check translations: '.$e->getMessage());
        }

        return Response::text(json_encode([
            'format' => $report['from'],
            'locales' => $report['locales'],
            'passed' => $report['totalMissing'] === 0,
            'totalMissing' => $report['totalMissing'],
            'sides' => $report['sides'],
            'orphans' => $report['orphans'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
