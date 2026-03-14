<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed order matters — each seeder depends on records created by prior ones.
     *
     * 1. UserSeeder            → Creates all users (admins, mods, regulars)
     * 2. IdentityVerificationSeeder → Requires users
     * 3. ProjectSeeder         → Requires users; creates projects + team members + applications
     * 4. CollaborationSeeder   → Requires users + projects
     * 5. CommunicationSeeder   → Requires users + projects + team members
     * 6. AdministrationSeeder  → Requires users; seeds system settings, reports, restrictions
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            IdentityVerificationSeeder::class,
            ProjectSeeder::class,
            CollaborationSeeder::class,
            CommunicationSeeder::class,
            AdministrationSeeder::class,
            MissingTablesSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('✅  Co-Found Platform database seeded successfully.');
        $this->command->info('');
        $this->command->info('  admin@cofound.io      / Admin@12345');
        $this->command->info('  moderator@cofound.io  / Mod@12345');
        $this->command->info('  demo@cofound.io       / Demo@12345');
        $this->command->info('');
    }
}
