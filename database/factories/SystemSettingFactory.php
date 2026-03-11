<?php

namespace Database\Factories;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SystemSettingFactory extends Factory
{
    protected $model = SystemSetting::class;

    public function definition(): array
    {
        $types = ['string', 'boolean', 'integer', 'array'];
        $type = $this->faker->randomElement($types);

        return [
            'id' => Str::uuid(),
            'setting_key' => $this->faker->unique()->word() . '_' . $this->faker->word(),
            'setting_value' => $this->getValueForType($type),
            'setting_type' => $type,
            'description' => $this->faker->sentence(),
            'is_public' => $this->faker->boolean(70),
            'updated_by' => $this->faker->optional(0.5)->randomElement([User::factory()]),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    private function getValueForType($type)
    {
        switch ($type) {
            case 'string':
                return $this->faker->word();
            case 'boolean':
                return $this->faker->boolean();
            case 'integer':
                return $this->faker->numberBetween(1, 1000);
            case 'array':
                return [
                    'key1' => $this->faker->word(),
                    'key2' => $this->faker->numberBetween(1, 100),
                    'key3' => $this->faker->boolean(),
                ];
            default:
                return $this->faker->word();
        }
    }

    public function string(): static
    {
        return $this->state([
            'setting_type' => 'string',
            'setting_value' => $this->faker->word(),
        ]);
    }

    public function boolean(): static
    {
        return $this->state([
            'setting_type' => 'boolean',
            'setting_value' => $this->faker->boolean(),
        ]);
    }

    public function integer(): static
    {
        return $this->state([
            'setting_type' => 'integer',
            'setting_value' => $this->faker->numberBetween(1, 1000),
        ]);
    }

    public function array(): static
    {
        return $this->state([
            'setting_type' => 'array',
            'setting_value' => [
                $this->faker->word() => $this->faker->word(),
                $this->faker->word() => $this->faker->numberBetween(1, 100),
            ],
        ]);
    }

    public function public(): static
    {
        return $this->state([
            'is_public' => true,
        ]);
    }

    public function private(): static
    {
        return $this->state([
            'is_public' => false,
        ]);
    }
}
