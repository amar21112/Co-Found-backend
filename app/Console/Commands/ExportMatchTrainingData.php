<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Exports match records and their features as a flat CSV/JSON
 * ready for ML model training.
 *
 * Each row contains:
 *   - All feature columns from match_reasons (JSON-expanded)
 *   - The compatibility_score (ground truth / target for regression)
 *   - feedback_type label (for classification training)
 *   - match_type (collaborator | project)
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
        {--format=csv       : Output format: csv or json}
        {--type=            : Filter by match type: collaborator or project}
        {--min-score=0      : Minimum compatibility score}
        {--with-feedback-only : Only export matches that have user feedback}
        {--output=          : Output file path (default: storage/app/ml/matches_{type}_{date}.csv)}';

    protected $description = 'Export match training data for the ML recommendation model';

    public function handle(): int
    {
        $format          = $this->option('format');
        $type            = $this->option('type');
        $minScore        = (float) $this->option('min-score');
        $feedbackOnly    = $this->option('with-feedback-only');

        $this->info('Exporting match training data...');
        $this->info("  Format:        $format");
        $this->info("  Type filter:   " . ($type ?: 'all'));
        $this->info("  Min score:     $minScore");
        $this->info("  Feedback only: " . ($feedbackOnly ? 'yes' : 'no'));

        // ── Query ─────────────────────────────────────────────────────────────
        $query = DB::table('matches AS m')
            ->leftJoin('match_feedback AS mf', function ($join) {
                $join->on('mf.match_id', '=', 'm.id')
                    ->whereRaw('mf.user_id = m.user_id');
            })
            ->leftJoin('users AS u', 'u.id', '=', 'm.user_id')
            ->leftJoin('users AS mu', 'mu.id', '=', 'm.matched_user_id')
            ->leftJoin('projects AS mp', 'mp.id', '=', 'm.matched_project_id')
            ->select([
                'm.id',
                'm.match_type',
                'm.compatibility_score',
                'm.match_reasons',
                'm.viewed',
                'm.saved',
                'm.action_taken',
                'mf.feedback_type',
                // User signals
                'u.identity_verified AS user_identity_verified',
                'u.location AS user_location',
                // Matched user signals (collaborator)
                'mu.identity_verified AS matched_user_identity_verified',
                'mu.location AS matched_user_location',
                // Matched project signals
                'mp.is_accepting_applications AS project_accepting',
                'mp.current_team_size',
                'mp.team_size_max',
                'm.created_at',
            ])
            ->where('m.compatibility_score', '>=', $minScore)
            ->orderBy('m.created_at');

        if ($type) {
            $query->where('m.match_type', $type);
        }

        if ($feedbackOnly) {
            $query->whereNotNull('mf.feedback_type');
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->warn('No matching records found. Run the MLMatchDatasetSeeder first.');
            return self::FAILURE;
        }

        $this->info("  Records found: {$rows->count()}");

        // ── Flatten match_reasons JSON into columns ────────────────────────────
        $flattened = $rows->map(function ($row) {
            $reasons = json_decode($row->match_reasons ?? '{}', true) ?: [];

            // Convert overlapping_skills array to count (ML needs scalars)
            $reasons['overlapping_skills_count'] = count($reasons['overlapping_skills'] ?? []);
            $reasons['covered_skills_count']     = count($reasons['covered_skills']     ?? []);
            unset($reasons['overlapping_skills'], $reasons['covered_skills']);

            return array_merge([
                'id'                       => $row->id,
                'match_type'               => $row->match_type,
                'compatibility_score'      => $row->compatibility_score,
                'viewed'                   => (int) $row->viewed,
                'saved'                    => (int) $row->saved,
                'action_taken'             => (int) $row->action_taken,
                'feedback_type'            => $row->feedback_type ?? '',
                // Convert feedback to binary label for classification
                'label_relevant'           => (int) ($row->feedback_type === 'relevant'),
                'label_not_relevant'       => (int) ($row->feedback_type === 'not_relevant'),
                // User signals
                'user_identity_verified'   => (int) $row->user_identity_verified,
                'same_location'            => $row->user_location && $row->matched_user_location
                    ? (int) ($row->user_location === $row->matched_user_location)
                    : ($reasons['location_match'] ?? 0),
                // Project signals
                'project_accepting'        => $row->project_accepting ?? 0,
                'team_openness'            => $row->team_size_max > 0
                    ? round(($row->team_size_max - $row->current_team_size) / $row->team_size_max, 3)
                    : ($reasons['team_openness'] ?? 0),
            ], $reasons);
        });

        // ── Output ────────────────────────────────────────────────────────────
        $outputPath = $this->option('output')
            ?: storage_path('app/ml/matches_' . ($type ?: 'all') . '_' . now()->format('Ymd_His') . '.' . $format);

        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if ($format === 'json') {
            $this->exportJson($flattened, $outputPath);
        } else {
            $this->exportCsv($flattened, $outputPath);
        }

        $this->info('');
        $this->info("  ✓ Exported {$rows->count()} rows to: $outputPath");
        $this->info('');
        $this->info('  Feature columns (ML inputs):');
        $this->info('    Collaborator: skill_overlap, complementarity, location_match,');
        $this->info('                  both_identity_verified, skills_count_a, skills_count_b');
        $this->info('    Project:      skill_coverage, team_openness, project_accepting,');
        $this->info('                  user_identity_verified, required_skills_count');
        $this->info('');
        $this->info('  Target columns (ML outputs):');
        $this->info('    Regression:     compatibility_score (0.0 – 1.0)');
        $this->info('    Classification: label_relevant (0 | 1)');

        return self::SUCCESS;
    }

    private function exportCsv($rows, string $path): void
    {
        $handle = fopen($path, 'w');

        // Header
        fputcsv($handle, array_keys($rows->first()->toArray()));

        // Data
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row->toArray()));
        }

        fclose($handle);
    }

    private function exportJson($rows, string $path): void
    {
        file_put_contents($path, $rows->toJson(JSON_PRETTY_PRINT));
    }
}
