<?php

namespace Database\Seeders;

use App\Models\SkillEndorsement;
use App\Models\UserSkill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SkillEndorsementSeeder extends Seeder
{
    public function run(): void
    {
        $userSkills = UserSkill::all();
        $users = User::all();

        foreach ($userSkills as $userSkill) {
            $endorsementCount = rand(0, 15);
            $endorsedUsers = []; // Track users who have already endorsed this skill

            for ($i = 0; $i < $endorsementCount; $i++) {
                // Get a random endorser that isn't the skill owner
                $endorser = $users->where('id', '!=', $userSkill->user_id)->random();

                // Check if this user has already endorsed this skill
                if (in_array($endorser->id, $endorsedUsers)) {
                    continue; // Skip if already endorsed
                }

                // Check if endorsement already exists in database
                $exists = SkillEndorsement::where('user_skill_id', $userSkill->id)
                    ->where('endorsed_by_user_id', $endorser->id)
                    ->exists();

                if (!$exists) {
                    SkillEndorsement::factory()->create([
                        'user_skill_id' => $userSkill->id,
                        'endorsed_by_user_id' => $endorser->id,
                    ]);

                    $endorsedUsers[] = $endorser->id; // Track this endorsement
                }
            }
        }
    }
}
