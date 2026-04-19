<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed order matters — each seeder depends on records created by prior ones.
     *
     * 1. UserSeeder                  → Creates all users (admins, mods, regulars, guests)
     * 2. IdentityVerificationSeeder  → Requires users
     * 3. ProjectSeeder               → Requires users; creates projects + team + applications
     * 4. CollaborationSeeder         → Requires users + projects
     * 5. CommunicationSeeder         → Requires users + projects + team members
     * 6. AdministrationSeeder        → Requires users; seeds settings, reports, restrictions
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

        $this->command->info('✅  Co-Found Platform database seeded successfully.');
        $this->command->info('   Run UserSeeder output above for dev account credentials.');
    }
}
