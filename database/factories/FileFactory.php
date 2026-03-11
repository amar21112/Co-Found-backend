<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        $mimeTypes = [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf', 'application/msword',
            'application/zip', 'text/plain'
        ];

        $extensions = ['jpg', 'png', 'pdf', 'docx', 'zip', 'txt'];

        return [
            'id' => Str::uuid(),
            'uploader_id' => User::factory(),
            'file_name' => $this->faker->word() . '.' . $this->faker->randomElement($extensions),
            'file_size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'mime_type' => $this->faker->randomElement($mimeTypes),
            'storage_path' => 'uploads/' . $this->faker->uuid() . '/' . $this->faker->word() . '.' . $this->faker->fileExtension(),
            'public_url' => $this->faker->optional(0.8)->url(),
            'thumbnail_url' => $this->faker->optional(0.3)->imageUrl(300, 300),
            'file_hash' => $this->faker->sha256(),
            'upload_completed' => $this->faker->boolean(90),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function image(): static
    {
        return $this->state([
            'mime_type' => 'image/jpeg',
            'file_name' => $this->faker->word() . '.jpg',
            'thumbnail_url' => $this->faker->imageUrl(300, 300),
        ]);
    }

    public function document(): static
    {
        return $this->state([
            'mime_type' => 'application/pdf',
            'file_name' => $this->faker->word() . '.pdf',
            'thumbnail_url' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'upload_completed' => true,
        ]);
    }
}
