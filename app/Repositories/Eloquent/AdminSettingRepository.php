<?php

namespace App\Repositories\Eloquent;

use App\Models\ConfigurationHistory;
use App\Models\SystemSetting;
use App\Repositories\Contracts\AdminSettingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminSettingRepository implements AdminSettingRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = SystemSetting::with('updatedBy:id,username,full_name')
            ->orderBy('setting_key');

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('setting_key', 'LIKE', $term)
                  ->orWhere('description', 'LIKE', $term);
            });
        }

        if (!empty($filters['setting_type'])) {
            $query->where('setting_type', $filters['setting_type']);
        }

        if (isset($filters['is_public']) && $filters['is_public'] !== '') {
            $query->where(
                'is_public',
                filter_var($filters['is_public'], FILTER_VALIDATE_BOOLEAN),
            );
        }

        return $query->paginate($perPage);
    }

    public function findByKey(string $key): ?SystemSetting
    {
        return SystemSetting::with('updatedBy:id,username,full_name')->where('setting_key', $key)->first();
    }

    public function update(SystemSetting $setting, array $data, string $changedBy, ?string $reason): SystemSetting
    {
        $oldValue = $setting->setting_value;

        $setting->update(array_merge($data, ['updated_by' => $changedBy]));

        // Record audit history
        ConfigurationHistory::create([
            'setting_key'   => $setting->setting_key,
            'old_value'     => $oldValue,
            'new_value'     => $setting->fresh()->setting_value,
            'changed_by'    => $changedBy,
            'change_reason' => $reason,
        ]);

        return $setting->fresh('updatedBy');
    }

    public function historyForSetting(string $settingKey, int $perPage): LengthAwarePaginator
    {
        return ConfigurationHistory::with('changedBy:id,username,full_name')
            ->where('setting_key', $settingKey)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
