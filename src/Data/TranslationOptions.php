<?php

namespace BrunosCode\TranslationHandler\Data;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;

class TranslationOptions
{
    const PHP = 'php_file';

    const CSV = 'csv_file';

    const JSON = 'json_file';

    const DB = 'db';

    const TYPES = [
        self::PHP,
        self::CSV,
        self::JSON,
        self::DB,
    ];

    public string $keyDelimiter;

    public array $fileNames;

    public array $locales;

    public string $phpHandlerClass;

    public string $dbHandlerClass;

    public string $csvHandlerClass;

    public string $jsonHandlerClass;

    public string $defaultImportFrom;

    public string $defaultImportTo;

    public string $defaultExportFrom;

    public string $defaultExportTo;

    public string $phpPath;

    public bool $phpFormat;

    public string $jsonPath;

    public string $csvPath;

    public string $csvFileName;

    public string $csvDelimiter;

    public function __construct(?array $config = null)
    {
        $validator = self::validator($config ?? config('translation-handler'));

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        $validated = $validator->validated();

        $this->keyDelimiter = $validated['keyDelimiter'];

        $this->fileNames = $validated['fileNames'];
        $this->locales = $validated['locales'];

        $this->phpHandlerClass = $validated['phpHandlerClass'];
        $this->dbHandlerClass = $validated['dbHandlerClass'];
        $this->csvHandlerClass = $validated['csvHandlerClass'];
        $this->jsonHandlerClass = $validated['jsonHandlerClass'];

        $this->defaultImportFrom = $validated['defaultImportFrom'];
        $this->defaultImportTo = $validated['defaultImportTo'];
        $this->defaultExportFrom = $validated['defaultExportFrom'];
        $this->defaultExportTo = $validated['defaultExportTo'];

        $this->phpPath = $validated['phpPath'];
        $this->phpFormat = $validated['phpFormat'];

        $this->jsonPath = $validated['jsonPath'];

        $this->csvPath = $validated['csvPath'];
        $this->csvFileName = $validated['csvFileName'];
        $this->csvDelimiter = $validated['csvDelimiter'];
    }

    public function validator(array $data): ValidatorContract
    {
        return Validator::make($data, [
            'keyDelimiter' => 'required|string|min:1',

            'fileNames' => 'required|array',
            'fileNames.*' => 'required|string|distinct|min:1',

            'locales' => 'required|array|min:1',
            'locales.*' => 'required|string|distinct|min:2|max:7',

            'phpHandlerClass' => 'required|string|min:1',
            'dbHandlerClass' => 'required|string|min:1',
            'csvHandlerClass' => 'required|string|min:1',
            'jsonHandlerClass' => 'required|string|min:1',

            'defaultImportFrom' => 'required|string|in:'.implode(',', self::TYPES),
            'defaultImportTo' => 'required|string|in:'.implode(',', self::TYPES),
            'defaultExportFrom' => 'required|string|in:'.implode(',', self::TYPES),
            'defaultExportTo' => 'required|string|in:'.implode(',', self::TYPES),

            'phpPath' => 'required|string|min:1',
            'phpFormat' => 'required|boolean',

            'jsonPath' => 'required|string|min:1',

            'csvPath' => 'required|string|min:1',
            'csvFileName' => 'required|string|min:1',
            'csvDelimiter' => 'required|string|min:1|different:'.$data['keyDelimiter'],
        ]);
    }
}
