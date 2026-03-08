<?php

namespace Database\Factories;

use App\Models\ConfigurationHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ConfigurationHistoryFactory extends Factory
{
    protected $model = ConfigurationHistory::class;

    public function definition(): array
    {
        $oldValue = $this->faker->word();
        $newValue = $this->faker->word();

        return [
            'id' => Str::uuid(),
            'setting_key' => $this->faker->word() . '_' . $this->faker->word(),
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'changed_by' => User::factory(),
            'change_reason' => $this->faker->optional(0.7)->sentence(),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function withReason(): static
    {
        return $this->state(fn (array $attributes) => [
            'change_reason' => $this->faker->sentence(),
        ]);
    }

    public function forKey($key): static
    {
        return $this->state(fn (array $attributes) => [
            'setting_key' => $key,
        ]);
    }
}
