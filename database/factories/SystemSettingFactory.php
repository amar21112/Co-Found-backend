<?php

namespace Database\Factories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemSettingFactory extends Factory
{
    protected $model = SystemSetting::class;

    public function definition(): array
    {
        return [
            'id'            => $this->faker->uuid(),
            'setting_key'   => $this->faker->unique()->slug(3),
            'setting_value' => json_encode($this->faker->word()),
            'setting_type'  => $this->faker->randomElement(['string', 'integer', 'boolean', 'json']),
            'description'   => $this->faker->sentence(),
            'is_public'     => false,
            'updated_by'    => null,
        ];
    }
}
