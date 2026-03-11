<?php

namespace Database\Factories;

use App\Models\PortfolioItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PortfolioItemFactory extends Factory
{
    protected $model = PortfolioItem::class;

    public function definition(): array
    {
        $types = ['image', 'document', 'video', 'link', 'code'];
        $visibilities = ['public', 'private', 'connections'];

        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional(0.8)->paragraphs(3, true),
            'file_url' => $this->faker->optional(0.7)->imageUrl(800, 600, 'business'),
            'thumbnail_url' => $this->faker->optional(0.5)->imageUrl(300, 200, 'business'),
            'item_type' => $this->faker->randomElement($types),
            'external_url' => $this->faker->optional(0.3)->url(),
            'visibility' => $this->faker->randomElement($visibilities),
            'is_featured' => $this->faker->boolean(20),
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    public function public(): static
    {
        return $this->state([
            'visibility' => 'public',
        ]);
    }

    public function featured(): static
    {
        return $this->state([
            'is_featured' => true,
        ]);
    }

    public function image(): static
    {
        return $this->state([
            'item_type' => 'image',
            'file_url' => $this->faker->imageUrl(800, 600, 'business'),
        ]);
    }

    public function video(): static
    {
        return $this->state([
            'item_type' => 'video',
            'file_url' => $this->faker->url() . '/video.mp4',
        ]);
    }

    public function code(): static
    {
        return $this->state([
            'item_type' => 'code',
            'external_url' => $this->faker->url() . '/github',
        ]);
    }
}
