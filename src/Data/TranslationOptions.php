<?php

namespace BrunosCode\TranslationHandler\Data;

use BrunosCode\TranslationHandler\TranslationChecker;
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

    /**
     * Used when the config has no `check` entry (published before the option
     * existed). Mirrors the shipped config/translation-handler.php.
     */
    const DEFAULT_CHECK = [
        'backend' => [
            'paths' => ['app', 'resources/views', 'routes', 'database'],
            'extensions' => ['php'],
        ],
        'frontend' => [
            'paths' => ['resources/js'],
            'extensions' => ['ts', 'tsx', 'js', 'jsx'],
        ],
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

    public bool $csvEscapeFormulas;

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
        // jsonFileName, jsonNested, jsonFormat, csvEscapeFormulas, check,
        // checkIncludeFrameworkKeys and checkerClass are optional with the
        // shipped defaults, like phpPint above: a config published (or cached)
        // before an option existed keeps working after upgrade.
        $this->jsonFileName = $validated['jsonFileName'] ?? null;
        $this->jsonNested = $validated['jsonNested'] ?? false;
        $this->jsonFormat = $validated['jsonFormat'] ?? true;

        $this->csvPath = $validated['csvPath'];
        $this->csvFileName = $validated['csvFileName'];
        $this->csvDelimiter = $validated['csvDelimiter'];
        $this->csvEscapeFormulas = $validated['csvEscapeFormulas'] ?? true;

        // Read check/checkerClass from the raw config so the full structure
        // (including optional per-side `patterns`) is preserved — validated()
        // only returns the leaves it has explicit rules for.
        $this->check = $config['check'] ?? self::DEFAULT_CHECK;
        $this->checkIncludeFrameworkKeys = $validated['checkIncludeFrameworkKeys'] ?? false;
        $this->checkerClass = $config['checkerClass'] ?? TranslationChecker::class;
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
            'jsonFileName' => 'sometimes|nullable|string|min:0',
            'jsonNested' => 'sometimes|boolean',
            'jsonFormat' => 'sometimes|boolean',

            'csvPath' => 'required|string|min:1',
            'csvFileName' => 'required|string|min:1',
            // `different:` takes the name of another field, not a literal value.
            'csvDelimiter' => 'required|string|min:1|different:keyDelimiter',
            'csvEscapeFormulas' => 'sometimes|boolean',

            'check' => 'sometimes|array|min:1',
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

            'checkerClass' => 'sometimes|string|min:1',
        ], [
            'fileNames.*.distinct' => 'Duplicate file name ":input" in fileNames',
            'locales.*.distinct' => 'Duplicate locale ":input" in locales',
            'csvDelimiter.different' => 'csvDelimiter (":input") must be different from keyDelimiter ("'.($data['keyDelimiter'] ?? '').'")',
            'defaultImportFrom.in' => 'Invalid defaultImportFrom ":input". Valid types: '.$validTypes,
            'defaultImportTo.in' => 'Invalid defaultImportTo ":input". Valid types: '.$validTypes,
            'defaultExportFrom.in' => 'Invalid defaultExportFrom ":input". Valid types: '.$validTypes,
            'defaultExportTo.in' => 'Invalid defaultExportTo ":input". Valid types: '.$validTypes,
        ]);
    }
}
