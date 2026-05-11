<?php

namespace App\Console\Commands;

use App\DTOs\Match\ExportDatasetDTO;
use App\Services\MatchService;
use Illuminate\Console\Command;

/**
 * ExportMatchTrainingData
 *
 * Thin Artisan wrapper around MatchService::exportTrainingData().
 * All query and flattening logic lives in MatchRepository.
 *
 * Usage:
 *   php artisan ml:export-matches
 *   php artisan ml:export-matches --format=json
 *   php artisan ml:export-matches --type=collaborator --output=storage/app/ml/collab.csv
 *   php artisan ml:export-matches --min-score=0.5 --with-feedback-only
 */
class ExportMatchTrainingData extends Command
{
    protected $signature = 'ml:export-matches
        {--format=csv          : Output format: csv or json}
        {--type=               : Filter by match type: collaborator or project}
        {--min-score=0         : Minimum compatibility score (0–1)}
        {--with-feedback-only  : Only export rows with user feedback}
        {--output=             : Output path (default: storage/app/ml/matches_{type}_{date}.{format})}';

    protected $description = 'Export match training data for the ML recommendation model';

    public function __construct(
        private readonly MatchService $matchService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $format       = $this->option('format');
        $type         = $this->option('type') ?: null;
        $minScore     = (float) $this->option('min-score');
        $feedbackOnly = (bool)  $this->option('with-feedback-only');

        $this->info('Exporting match training data...');
        $this->info("  Format:         $format");
        $this->info("  Type filter:    " . ($type ?: 'all'));
        $this->info("  Min score:      $minScore");
        $this->info("  Feedback only:  " . ($feedbackOnly ? 'yes' : 'no'));

        $rows = $this->matchService->exportTrainingData(new ExportDatasetDTO(
            format:          $format,
            type:            $type,
            minScore:        $minScore,
            withFeedbackOnly:$feedbackOnly,
        ));

        if ($rows->isEmpty()) {
            $this->warn('No records found. Run the MLMatchDatasetSeeder first.');
            return self::FAILURE;
        }

        $outputPath = $this->option('output')
            ?: storage_path('app/ml/matches_' . ($type ?: 'all') . '_' . now()->format('Ymd_His') . '.' . $format);

        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $format === 'json'
            ? file_put_contents($outputPath, $rows->toJson(JSON_PRETTY_PRINT))
            : $this->writeCsv($rows, $outputPath);

        $this->info('');
        $this->info("  ✓ {$rows->count()} rows exported to: $outputPath");

        return self::SUCCESS;
    }

    private function writeCsv($rows, string $path): void
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, array_keys($rows->first()));
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }
        fclose($handle);
    }
}
