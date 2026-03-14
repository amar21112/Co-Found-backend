<?php

namespace Database\Factories;

use App\Models\PortfolioItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PortfolioItemFactory extends Factory
{
    protected $model = PortfolioItem::class;

    public function definition(): array
    {
        return [
            'id'            => $this->faker->uuid(),
            'user_id'       => User::factory(),
            'title'         => $this->faker->catchPhrase(),
            'description'   => $this->faker->paragraph(3),
            'file_url'      => $this->faker->url(),
            'thumbnail_url' => $this->faker->imageUrl(400, 300),
            'item_type'     => $this->faker->randomElement(['image', 'document', 'video', 'link', 'code']),
            'external_url'  => $this->faker->url(),
            'visibility'    => $this->faker->randomElement(['public', 'private', 'connections']),
            'is_featured'   => $this->faker->boolean(20),
        ];
    }
}
