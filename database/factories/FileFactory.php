<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        $ext = $this->faker->randomElement(['pdf', 'png', 'jpg', 'docx', 'mp4']);

        return [
            'id'               => $this->faker->uuid(),
            'uploader_id'      => User::factory(),
            'file_name'        => $this->faker->word() . '.' . $ext,
            'file_size'        => $this->faker->numberBetween(1024, 10 * 1024 * 1024),
            'mime_type'        => $this->faker->mimeType(),
            'storage_path'     => 'uploads/' . $this->faker->uuid() . '.' . $ext,
            'public_url'       => null,
            'thumbnail_url'    => null,
            'file_hash'        => $this->faker->sha256(),
            'upload_completed' => true,
        ];
    }
}
