<?php

namespace App\Repositories\Contracts;

use App\Models\SystemSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AdminSettingRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function findByKey(string $key): ?SystemSetting;

    public function update(SystemSetting $setting, array $data, string $changedBy, ?string $reason): SystemSetting;

    public function historyForSetting(string $settingKey, int $perPage): LengthAwarePaginator;
}
