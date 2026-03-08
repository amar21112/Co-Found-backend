<?php

namespace Database\Seeders;

use App\Models\ContentModeration;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContentModerationSeeder extends Seeder
{
    public function run(): void
    {
        $moderators = User::where('role', 'moderator')->get();

        for ($i = 0; $i < 100; $i++) {
            $moderator = $moderators->random();
            $action = $this->getRandomAction();

            $factory = ContentModeration::factory();

            if ($action === 'approved') {
                $factory->approved();
            } elseif ($action === 'edited') {
                $factory->edited();
            } elseif ($action === 'removed') {
                $factory->removed();
            }

            if (rand(0, 1)) {
                $factory->reported();
            } else {
                $factory->autoFlagged();
            }

            $factory->create([
                'moderator_id' => $moderator->id,
                'action_taken' => $action
            ]);
        }
    }

    private function getRandomAction()
    {
        $actions = ['approved', 'edited', 'removed', 'quarantined', 'escalated'];
        return $actions[array_rand($actions)];
    }
}
