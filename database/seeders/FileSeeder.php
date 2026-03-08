<?php

namespace Database\Seeders;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Seeder;

class FileSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('account_status', 'active')->get();

        foreach ($users->random(min(50, $users->count())) as $user) {
            $fileCount = rand(1, 10);

            for ($i = 0; $i < $fileCount; $i++) {
                $type = rand(0, 2);

                $factory = File::factory()
                    ->completed();

                if ($type === 0) {
                    $factory->image();
                } elseif ($type === 1) {
                    $factory->document();
                }

                $factory->create(['uploader_id' => $user->id]);
            }
        }
    }
}
