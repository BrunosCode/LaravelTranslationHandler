<?php

namespace BrunosCode\TranslationHandler\Commands;

use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationArguments;
use BrunosCode\TranslationHandler\Commands\Behaviors\HasTranslationOptions;
use BrunosCode\TranslationHandler\Facades\TranslationHandler;
use Illuminate\Console\Command;

class CheckCommand extends Command
{
    use HasTranslationArguments, HasTranslationOptions;

    public $signature = 'translation-handler:check
                            {from?}
                            {--from-path=}
                            {--locale=* : Restrict to one or more locales (defaults to configured)}
                            {--side= : backend|frontend (defaults to both)}
                            {--show-keys : Print each missing (or orphan) key}
                            {--orphans : Also list keys defined but never referenced in code}';

    public $description = 'Scan backend PHP and frontend JS/TS for translation usages and report missing or orphan keys per locale';

    public function handle(): int
    {
        $from = $this->getTranslationFromArgument();

        $fromPath = $this->getTranslationFromPathOption();

        /** @var string[] $locales */
        $locales = $this->option('locale') ?: (TranslationHandler::getOption('locales') ?? []);

        if (empty($locales)) {
            $this->error(__('No locales configured.'));

            return self::FAILURE;
        }

        /** @var string[] $configuredSides */
        $configuredSides = array_keys(TranslationHandler::getOption('check'));

        /** @var string|null $side */
        $side = $this->option('side');
        if ($side !== null && ! in_array($side, $configuredSides, true)) {
            $this->error(__('--side must be one of: :sides', ['sides' => implode(', ', $configuredSides)]));

            return self::FAILURE;
        }

        $sides = $side !== null ? [$side] : $configuredSides;

        $report = TranslationHandler::check(
            from: $from,
            locales: $locales,
            sides: $sides,
            fromPath: $fromPath,
            includeOrphans: (bool) $this->option('orphans'),
        );

        $this->renderReport($report);

        if ($report['totalMissing'] > 0) {
            $this->newLine();
            $this->error(__(':count missing translation(s) found.', ['count' => $report['totalMissing']]));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function renderReport(array $report): void
    {
        $showKeys = (bool) $this->option('show-keys');

        foreach ($report['sides'] as $side => $sideReport) {
            $this->newLine();
            $this->line('<fg=cyan;options=bold>──────── '.strtoupper($side).' ────────</>');
            $this->line(sprintf(
                'Found <fg=yellow>%d</> static keys and <fg=yellow>%d</> dynamic prefixes.',
                $sideReport['staticKeys'],
                $sideReport['prefixes'],
            ));
            $this->newLine();

            foreach ($sideReport['locales'] as $locale => $missing) {
                $color = $missing['total'] === 0 ? 'green' : 'red';

                $this->line(sprintf(
                    '<fg=%s;options=bold>%s</>: <fg=%s>%d</> missing (<fg=%s>%d</> keys + <fg=%s>%d</> prefixes)',
                    $color,
                    $locale,
                    $color,
                    $missing['total'],
                    $color,
                    count($missing['keys']),
                    $color,
                    count($missing['prefixes']),
                ));

                if ($showKeys && $missing['total'] > 0) {
                    foreach ($missing['keys'] as $key) {
                        $this->line("  <fg=red>•</> {$key}");
                    }
                    foreach ($missing['prefixes'] as $prefix) {
                        $this->line("  <fg=red>•</> {$prefix}<fg=gray>* (no key matches this prefix)</>");
                    }
                }
            }
        }

        if ($report['orphans'] === null) {
            return;
        }

        $this->newLine();
        $this->line('<fg=cyan;options=bold>──────── ORPHAN KEYS ────────</>');
        $sideLabel = count($report['sides']) === 2 ? 'all sides' : (string) array_key_first($report['sides']);
        $this->line("<fg=gray>(defined but never referenced in {$sideLabel})</>");
        $this->newLine();

        foreach ($report['orphans'] as $locale => $orphans) {
            $color = count($orphans) === 0 ? 'green' : 'yellow';
            $this->line(sprintf(
                '<fg=%s;options=bold>%s</>: <fg=%s>%d</> orphan key(s)',
                $color,
                $locale,
                $color,
                count($orphans),
            ));

            if ($showKeys) {
                foreach ($orphans as $key) {
                    $this->line("  <fg=yellow>•</> {$key}");
                }
            }
        }
    }
}
