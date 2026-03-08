<?php

namespace Database\Seeders;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class SystemLogSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 0; $i < 500; $i++) {
            $level = $this->getRandomLogLevel();

            $factory = SystemLog::factory();

            if ($level === 'debug') {
                $factory->debug();
            } elseif ($level === 'info') {
                $factory->info();
            } elseif ($level === 'warning') {
                $factory->warning();
            } elseif ($level === 'error') {
                $factory->error();
            } elseif ($level === 'critical') {
                $factory->critical();
            }

            $factory->create([
                'log_level' => $level,
                'user_id' => rand(0, 1) ? User::inRandomOrder()->first()?->id : null,
                'created_at' => now()->subHours(rand(0, 168))->subMinutes(rand(0, 59))
            ]);
        }
    }

    private function getRandomLogLevel()
    {
        $levels = ['debug', 'info', 'warning', 'error', 'critical'];
        $weights = [30, 40, 15, 10, 5];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($weights as $index => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $levels[$index];
            }
        }

        return 'info';
    }
}
