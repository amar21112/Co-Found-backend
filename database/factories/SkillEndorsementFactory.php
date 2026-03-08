<?php

namespace Database\Factories;

use App\Models\SkillEndorsement;
use App\Models\UserSkill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SkillEndorsementFactory extends Factory
{
    protected $model = SkillEndorsement::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_skill_id' => UserSkill::factory(),
            'endorsed_by_user_id' => User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
