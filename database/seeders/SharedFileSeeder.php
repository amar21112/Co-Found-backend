<?php

namespace Database\Seeders;

use App\Models\SharedFile;
use App\Models\File;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class SharedFileSeeder extends Seeder
{
    public function run(): void
    {
        $files = File::where('upload_completed', true)->get();
        $conversations = Conversation::all();
        $messages = Message::where('message_type', 'file')->get();

        foreach ($files->random(min(100, $files->count())) as $file) {
            $sharedInMessage = rand(0, 1) && $messages->count() > 0;
            $sharedInConversation = !$sharedInMessage && $conversations->count() > 0;

            $factory = SharedFile::factory();

            if (rand(0, 2) === 0) {
                $factory->viewOnly();
            } elseif (rand(0, 1)) {
                $factory->downloadable();
            }

            if (rand(0, 3) === 0) {
                $factory->expiring();
            }

            $factory->create([
                'file_id' => $file->id,
                'conversation_id' => $sharedInConversation ? $conversations->random()->id : null,
                'message_id' => $sharedInMessage ? $messages->random()->id : null,
                'shared_by' => $file->uploader_id
            ]);
        }
    }
}
