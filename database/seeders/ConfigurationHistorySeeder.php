<?php

namespace Database\Seeders;

use App\Models\ConfigurationHistory;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConfigurationHistorySeeder extends Seeder
{
    public function run(): void
    {
        $settings = SystemSetting::all();
        $admin = User::where('role', 'administrator')->first();

        foreach ($settings as $setting) {
            $changeCount = rand(0, 5);

            for ($i = 0; $i < $changeCount; $i++) {
                $oldValue = $setting->setting_value;
                $newValue = $this->getRandomValue($setting->setting_type, $oldValue);

                ConfigurationHistory::factory()
                    ->forKey($setting->setting_key)
                    ->when(rand(0, 1), fn($factory) => $factory->withReason())
                    ->create([
                        'setting_key' => $setting->setting_key,
                        'old_value' => $oldValue,
                        'new_value' => $newValue,
                        'changed_by' => $admin?->id,
                        'created_at' => now()->subDays(rand(1, 90))
                    ]);
            }
        }
    }

    private function getRandomValue($type, $oldValue)
    {
        switch ($type) {
            case 'boolean':
                return !$oldValue;
            case 'integer':
                return is_numeric($oldValue) ? $oldValue + rand(-5, 5) : rand(1, 100);
            case 'string':
                return $oldValue . ' (updated)';
            case 'array':
                $newArray = is_array($oldValue) ? $oldValue : [];
                $newArray[] = 'new_item_' . rand(1, 100);
                return $newArray;
            default:
                return $oldValue;
        }
    }
}
