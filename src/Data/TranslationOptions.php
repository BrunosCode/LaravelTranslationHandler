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

    const SORTABLE_TYPES = [
        self::PHP,
        self::CSV,
        self::JSON,
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

    public bool $phpPint;

    public string $jsonPath;

    public ?string $jsonFileName;

    public bool $jsonNested;

    public bool $jsonFormat;

    public string $csvPath;

    public string $csvFileName;

    public string $csvDelimiter;

    /** @var array<string, array{paths: string[], extensions: string[]}> */
    public array $check;

    public bool $checkIncludeFrameworkKeys;

    public string $checkerClass;

    public function __construct(?array $config = null)
    {
        $config = $config ?? config('translation-handler');

        $validator = self::validator($config);

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
        // Optional with a default so configs published before this option existed
        // (e.g. a cached config that mergeConfigFrom can no longer backfill) keep
        // working after upgrade instead of failing validation.
        $this->phpPint = $validated['phpPint'] ?? false;

        $this->jsonPath = $validated['jsonPath'];
        $this->jsonFileName = $validated['jsonFileName'];
        $this->jsonNested = $validated['jsonNested'];
        $this->jsonFormat = $validated['jsonFormat'];

        $this->csvPath = $validated['csvPath'];
        $this->csvFileName = $validated['csvFileName'];
        $this->csvDelimiter = $validated['csvDelimiter'];

        // Read check/checkerClass from the raw config so the full structure
        // (including optional per-side `patterns`) is preserved — validated()
        // only returns the leaves it has explicit rules for. Both are required
        // by the validator above, so they are guaranteed to be present here.
        $this->check = $config['check'];
        // Optional with a default, like phpPint above, so configs published
        // before this option existed keep working after upgrade.
        $this->checkIncludeFrameworkKeys = $validated['checkIncludeFrameworkKeys'] ?? false;
        $this->checkerClass = $config['checkerClass'];
    }

    public function validator(array $data): ValidatorContract
    {
        $validTypes = implode(', ', self::TYPES);

        $validRegex = function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || @preg_match($value, '') === false) {
                $fail("The {$attribute} is not a valid regular expression.");
            }
        };

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
            'phpPint' => 'sometimes|boolean',

            'jsonPath' => 'required|string|min:1',
            'jsonFileName' => 'nullable|string|min:0',
            'jsonNested' => 'required|boolean',
            'jsonFormat' => 'required|boolean',

            'csvPath' => 'required|string|min:1',
            'csvFileName' => 'required|string|min:1',
            'csvDelimiter' => 'required|string|min:1|different:'.$data['keyDelimiter'],

            'check' => 'required|array|min:1',
            'check.*' => 'required|array',
            'check.*.paths' => 'present|array',
            'check.*.paths.*' => 'required|string|min:1',
            'check.*.extensions' => 'present|array',
            'check.*.extensions.*' => 'required|string|min:1',
            'check.*.patterns' => 'sometimes|array',
            'check.*.patterns.static' => 'sometimes|array',
            'check.*.patterns.static.*' => ['required', 'string', 'min:1', $validRegex],
            'check.*.patterns.dynamic' => 'sometimes|array',
            'check.*.patterns.dynamic.*' => ['required', 'string', 'min:1', $validRegex],

            'checkIncludeFrameworkKeys' => 'sometimes|boolean',

            'checkerClass' => 'required|string|min:1',
        ], [
            'fileNames.*.distinct' => 'Duplicate file name ":input" in fileNames',
            'locales.*.distinct' => 'Duplicate locale ":input" in locales',
            'csvDelimiter.different' => 'csvDelimiter (":input") must be different from keyDelimiter ("'.$data['keyDelimiter'].'")',
            'defaultImportFrom.in' => 'Invalid defaultImportFrom ":input". Valid types: '.$validTypes,
            'defaultImportTo.in' => 'Invalid defaultImportTo ":input". Valid types: '.$validTypes,
            'defaultExportFrom.in' => 'Invalid defaultExportFrom ":input". Valid types: '.$validTypes,
            'defaultExportTo.in' => 'Invalid defaultExportTo ":input". Valid types: '.$validTypes,
        ]);
    }
}
