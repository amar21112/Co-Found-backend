<?php

namespace Database\Factories;

use App\Models\SkillEndorsement;
use App\Models\UserSkill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkillEndorsementFactory extends Factory
{
    protected $model = SkillEndorsement::class;

    public function definition(): array
    {
        return [
            'id'                  => $this->faker->uuid(),
            'user_skill_id'       => UserSkill::factory(),
            'endorsed_by_user_id' => User::factory(),
        ];
    }
}
