<?php

namespace BrunosCode\TranslationHandler\Mcp\Tools;

use BrunosCode\TranslationHandler\Collections\TranslationCollection;
use BrunosCode\TranslationHandler\Data\Translation;
use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SetTranslationGroupTool extends Tool
{
    protected string $description = 'Set or update every translation under a group prefix in a single call. Subkeys are joined to the group with the configured key delimiter, and each subkey may carry values for multiple locales.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'format' => $schema->string()
                ->description('Storage format to write to. Valid values: '.implode(', ', TranslationOptions::TYPES))
                ->enum(TranslationOptions::TYPES)
                ->required(),
            'group' => $schema->string()
                ->description('Group prefix (e.g. "auth"). Each subkey will be joined to this group with the configured key delimiter (e.g. "auth.welcome").')
                ->required(),
            'translations' => $schema->object()
                ->description('Object mapping subkey to a locale=>value object, e.g. {"welcome": {"en": "Welcome", "it": "Benvenuto"}, "logout": {"en": "Logout", "it": "Esci"}}.')
                ->required(),
            'force' => $schema->boolean()
                ->description('If true, overwrite existing values. Defaults to false (skip if already exists).'),
        ];
    }

    public function handle(Request $request): Response
    {
        $format = $request->get('format');
        $group = $request->get('group');
        $translations = $request->get('translations');
        $force = (bool) ($request->get('force') ?? false);

        if (! is_string($group) || $group === '') {
            return Response::error('The "group" parameter must be a non-empty string.');
        }

        if (! is_array($translations) || empty($translations)) {
            return Response::error('The "translations" parameter must be a non-empty object mapping subkeys to locale=>value maps.');
        }

        $delimiter = TranslationHandler::getOption('keyDelimiter') ?? '.';
        $group = rtrim($group, $delimiter);

        $items = [];
        $writtenKeys = [];

        foreach ($translations as $subkey => $localeValues) {
            if (! is_string($subkey) || $subkey === '') {
                return Response::error('Each translations entry must use a non-empty string subkey.');
            }

            if (! is_array($localeValues) || empty($localeValues)) {
                return Response::error("Subkey \"{$subkey}\" must map to a non-empty object of locale=>value pairs.");
            }

            $fullKey = $group.$delimiter.ltrim($subkey, $delimiter);
            $writtenKeys[] = $fullKey;

            foreach ($localeValues as $locale => $value) {
                if (! is_string($locale) || $locale === '') {
                    return Response::error("Subkey \"{$subkey}\" contains an invalid locale key.");
                }

                $items[] = new Translation($fullKey, $locale, $value);
            }
        }

        try {
            $collection = new TranslationCollection($items);
            $count = TranslationHandler::set($collection, $format, force: $force);
        } catch (\Throwable $e) {
            return Response::error('Failed to set translations: '.$e->getMessage());
        }

        return Response::text(json_encode([
            'written' => $count,
            'group' => $group,
            'keys' => $writtenKeys,
            'format' => $format,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
