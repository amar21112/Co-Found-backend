<?php

namespace Database\Seeders;

use App\Models\IdentityVerification;
use App\Models\VerificationReview;
use App\Models\User;
use Illuminate\Database\Seeder;

class IdentityVerificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('identity_verified', false)->get();

        foreach ($users->random(min(30, $users->count())) as $user) {
            $status = $this->getRandomStatus();

            $verification = IdentityVerification::factory()
                ->$status()
                ->create(['user_id' => $user->id]);

            if (in_array($status, ['verified', 'rejected'])) {
                $reviewer = User::where('role', 'moderator')->inRandomOrder()->first();

                if ($reviewer) {
                    VerificationReview::factory()
                        ->$status === 'verified' ? 'approved' : 'rejected'()
                        ->create([
                        'verification_id' => $verification->id,
                        'reviewer_id' => $reviewer->id
                    ]);
                }
            }
        }
    }

    private function getRandomStatus()
    {
        $statuses = [
            'pending' => 40,
            'underReview' => 20,
            'verified' => 25,
            'rejected' => 10,
            'escalated' => 5
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $status;
            }
        }

        return 'pending';
    }
}
