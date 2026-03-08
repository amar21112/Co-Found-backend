<?php

namespace Database\Seeders;

use App\Models\VerificationReview;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Database\Seeder;

class VerificationReviewSeeder extends Seeder
{
    public function run(): void
    {
        $verifications = IdentityVerification::whereIn('verification_status', ['verified', 'rejected'])->get();
        $moderators = User::where('role', 'moderator')->get();

        foreach ($verifications as $verification) {
            if (!$verification->reviews()->exists()) {
                $moderator = $moderators->random();

                $action = $verification->verification_status === 'verified' ? 'approved' : 'rejected';

                VerificationReview::factory()
                    ->$action()
                    ->create([
                        'verification_id' => $verification->id,
                        'reviewer_id' => $moderator->id,
                        'reviewed_at' => $verification->updated_at ?? now()->subHours(rand(1, 48))
                    ]);
            }
        }
    }
}
