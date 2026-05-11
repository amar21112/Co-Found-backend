<?php

namespace Database\Seeders\ML;

use App\Generators\MatchDatasetGenerator;
use Illuminate\Database\Seeder;

/**
 * MLMatchDatasetSeeder
 *
 * Thin Artisan wrapper around MatchDatasetGenerator.
 * All generation logic lives in the generator — this class only
 * handles CLI output.
 *
 * Run: php artisan db:seed --class=Database\\Seeders\\ML\\MLMatchDatasetSeeder
 */
class MLMatchDatasetSeeder extends Seeder
{
    public function __construct(
        private readonly MatchDatasetGenerator $generator,
    ) {}

    public function run(): void
    {
        $this->command->info('Generating ML match training dataset...');

        $result = $this->generator->generate();

        $this->command->info('');
        $this->command->info("  ✓ Users created:        {$result['users']}");
        $this->command->info("  ✓ Projects created:     {$result['projects']}");
        $this->command->info("  ✓ Collaborator matches: {$result['collaborator_matches']}");
        $this->command->info("  ✓ Project matches:      {$result['project_matches']}");
        $this->command->info('');
        $this->command->info('  Export with: php artisan ml:export-matches');
    }
}
