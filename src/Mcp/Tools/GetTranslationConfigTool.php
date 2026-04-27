<?php

namespace BrunosCode\TranslationHandler\Mcp\Tools;

use BrunosCode\TranslationHandler\Data\TranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
class GetTranslationConfigTool extends Tool
{
    protected string $description = 'Get the current translation-handler configuration: locales, file names, key delimiter, default formats, and paths for each storage format.';

    public function handle(Request $request): Response|ResponseFactory
    {
        $options = TranslationHandler::getOptions();

        return Response::structured([
            'locales' => $options->locales,
            'fileNames' => $options->fileNames,
            'keyDelimiter' => $options->keyDelimiter,
            'defaults' => [
                'importFrom' => $options->defaultImportFrom,
                'importTo' => $options->defaultImportTo,
                'exportFrom' => $options->defaultExportFrom,
                'exportTo' => $options->defaultExportTo,
            ],
            'formats' => [
                TranslationOptions::PHP => ['path' => $options->phpPath],
                TranslationOptions::JSON => ['path' => $options->jsonPath, 'fileName' => $options->jsonFileName, 'nested' => $options->jsonNested],
                TranslationOptions::CSV => ['path' => $options->csvPath, 'fileName' => $options->csvFileName, 'delimiter' => $options->csvDelimiter],
                TranslationOptions::DB => ['handler' => $options->dbHandlerClass],
            ],
        ]);
    }
}
