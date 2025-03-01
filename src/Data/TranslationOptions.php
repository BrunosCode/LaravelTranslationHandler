<?php

namespace BrunosCode\TranslationHandler\Data;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;

class TranslationOptions
{
    public const PHP = 'php_file';

    public const CSV = 'csv_file';

    public const JSON = 'json_file';

    public const DB = 'db';

    public const TYPES = [
        self::PHP,
        self::CSV,
        self::JSON,
        self::DB,
    ];

    public string $keyDelimiter;

    public array $locales;

    public string $phpHandlerClass;

    public string $dbHandlerClass;

    public string $csvHandlerClass;

    public string $jsonHandlerClass;

    public string $phpPath;

    public array $phpFileNames;

    public bool $phpFormat;

    public string $jsonPath;

    public ?string $jsonFileName;

    public bool $jsonNested;

    public bool $jsonFormat;

    public string $csvPath;

    public string $csvFileName;

    public string $csvDelimiter;

    public ?string $dbConnection;

    public string $defaultFromType;

    public ?string $defaultFromPath;

    public null|string|array $defaultFromFileNames;

    public string $defaultToType;

    public ?string $defaultToPath;

    public null|string|array $defaultToFileNames;

    public function __construct(?array $config = null)
    {
        $validator = self::validator($config ?? config('translation-handler'));

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        $validated = $validator->validated();

        $this->keyDelimiter = $validated['keyDelimiter'];

        $this->locales = $validated['locales'];

        $this->phpHandlerClass = $validated['phpHandlerClass'];
        $this->dbHandlerClass = $validated['dbHandlerClass'];
        $this->csvHandlerClass = $validated['csvHandlerClass'];
        $this->jsonHandlerClass = $validated['jsonHandlerClass'];

        $this->phpPath = $validated['phpPath'];
        $this->phpFormat = $validated['phpFormat'];

        $this->jsonPath = $validated['jsonPath'];
        $this->jsonFileName = $validated['jsonFileName'];
        $this->jsonNested = $validated['jsonNested'];
        $this->jsonFormat = $validated['jsonFormat'];

        $this->csvPath = $validated['csvPath'];
        $this->csvFileName = $validated['csvFileName'];
        $this->csvDelimiter = $validated['csvDelimiter'];

        $this->dbConnection = $validated['dbConnection'];

        $this->defaultFromType = $validated['defaultFromType'];
        $this->defaultFromPath = $validated['defaultFromPath'];
        $this->defaultFromFileNames = $validated['defaultFromFileNames'];
        $this->defaultToType = $validated['defaultToType'];
        $this->defaultToPath = $validated['defaultToPath'];
        $this->defaultToFileNames = $validated['defaultToFileNames'];
    }

    public function validator(array $data): ValidatorContract
    {
        return Validator::make($data, [
            'keyDelimiter' => 'required|string|min:1',

            'locales' => 'required|array|min:1',
            'locales.*' => 'required|string|distinct|min:2|max:7',

            'phpHandlerClass' => 'required|string|min:1',
            'dbHandlerClass' => 'required|string|min:1',
            'csvHandlerClass' => 'required|string|min:1',
            'jsonHandlerClass' => 'required|string|min:1',

            'phpPath' => 'required|string|min:1',
            'phpFileNames' => 'array',
            'phpFileNames.*' => 'distinct|string|min:1',
            'phpFormat' => 'required|boolean',

            'jsonPath' => 'required|string|min:1',
            'jsonFileName' => 'nullable|string|min:0',
            'jsonNested' => 'required|boolean',
            'jsonFormat' => 'required|boolean',

            'csvPath' => 'required|string|min:1',
            'csvFileName' => 'required|string|min:1',
            'csvDelimiter' => 'required|string|min:1|different:'.$data['keyDelimiter'],

            'dbConnection' => 'nullable|string|min:1',

            'defaultFromType' => 'required|in:'.implode(',', self::TYPES),
            'defaultFromPath' => 'nullable|string|min:1',
            'defaultFromFileNames' => 'nullable',
            'defaultToType' => 'required|in:'.implode(',', self::TYPES),
            'defaultToPath' => 'nullable|string|min:1',
            'defaultToFileNames' => 'nullable',
        ]);
    }
}
