<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\IdentityVerification;
use App\Models\VerificationReview;
use Illuminate\Database\Seeder;

class IdentityVerificationSeeder extends Seeder
{
    public function run(): void
    {
        $regularUsers = User::where('role', 'regular_user')->inRandomOrder()->take(30)->get();

        foreach ($regularUsers as $index => $user) {
            if ($index < 12) {
                // Verified submissions
                $verification = IdentityVerification::factory()->verified()->create(['user_id' => $user->id]);

                VerificationReview::factory()->create([
                    'verification_id' => $verification->id,
                    'review_action'   => 'approved',
                ]);

                $user->update([
                    'identity_verified'           => true,
                    'identity_verification_level' => 'advanced',
                ]);

            } elseif ($index < 20) {
                // Rejected submissions
                $verification = IdentityVerification::factory()->rejected()->create(['user_id' => $user->id]);

                VerificationReview::factory()->create([
                    'verification_id' => $verification->id,
                    'review_action'   => 'rejected',
                ]);

            } elseif ($index < 25) {
                // Under review
                IdentityVerification::factory()->underReview()->create(['user_id' => $user->id]);

            } else {
                // Pending (just submitted, not yet reviewed)
                IdentityVerification::factory()->create(['user_id' => $user->id]);
            }
        }

        $this->command->info('IdentityVerificationSeeder: 30 verification records seeded.');
    }
}
