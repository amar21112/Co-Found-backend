<?php

namespace Database\Seeders;

use App\Models\SkillEndorsement;
use App\Models\UserSkill;
use App\Models\User;
use Illuminate\Database\Seeder;

class SkillEndorsementSeeder extends Seeder
{
    public function run(): void
    {
        $userSkills = UserSkill::all();
        $users = User::all();

        foreach ($userSkills as $userSkill) {
            $endorsementCount = rand(0, 15);

            for ($i = 0; $i < $endorsementCount; $i++) {
                $endorser = $users->where('id', '!=', $userSkill->user_id)->random();

                SkillEndorsement::factory()
                    ->create([
                        'user_skill_id' => $userSkill->id,
                        'endorsed_by_user_id' => $endorser->id
                    ]);
            }
        }
    }
}
