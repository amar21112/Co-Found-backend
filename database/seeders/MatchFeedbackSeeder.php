<?php

namespace Database\Seeders;

use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\User;
use Illuminate\Database\Seeder;

class MatchFeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $matches = MatchModel::where('viewed', true)->get();

        foreach ($matches->random(min(100, $matches->count())) as $match) {
            $type = rand(0, 2) === 0 ? 'relevant' : (rand(0, 1) ? 'not_relevant' : 'already_connected');

            MatchFeedback::factory()
                ->$type()
                ->create([
                    'match_id' => $match->id,
                    'user_id' => $match->user_id
                ]);
        }
    }
}
