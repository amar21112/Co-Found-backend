<?php

namespace App\Services\Admin;

use App\DTOs\Admin\UpdateSettingDTO;
use App\Exceptions\Admin\SettingNotFoundException;
use App\Models\SystemSetting;
use App\Models\User;
use App\Repositories\Contracts\AdminSettingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class AdminSettingService
{
    public function __construct(
        private AdminSettingRepositoryInterface $settingRepo,
        private AdminActionLogger               $logger,
    ) {}

    // =========================================================================
    // List
    // =========================================================================

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->settingRepo->paginate($filters, $perPage);
    }

    // =========================================================================
    // Show by key
    // =========================================================================

    public function showByKey(string $key): SystemSetting
    {
        $setting = $this->settingRepo->findByKey($key);

        if (!$setting) {
            throw new SettingNotFoundException();
        }

        return $setting;
    }

    // =========================================================================
    // Update
    // =========================================================================

    /**
     * Update a system setting value and record the change in history.
     *
     * The ConfigurationHistory audit record is written inside the repository
     * so old_value is always captured before the update is committed.
     * Logged to admin_actions as well.
     */
    public function update(
        SystemSetting    $setting,
        UpdateSettingDTO $dto,
        User             $admin,
        string           $ip,
    ): SystemSetting {
        $updated = $this->settingRepo->update(
            setting:   $setting,
            data:      ['setting_value' => $dto->settingValue],
            changedBy: $admin->id,
            reason:    $dto->changeReason,
        );

        $this->logger->log(
            admin:      $admin,
            actionType: 'setting_updated',
            targetType: 'system_setting',
            targetId:   $setting->setting_key,
            details:    [
                'setting_key'   => $setting->setting_key,
                'change_reason' => $dto->changeReason,
            ],
            ip: $ip,
        );

        return $updated;
    }

    // =========================================================================
    // History
    // =========================================================================

    public function history(string $settingKey, int $perPage): LengthAwarePaginator
    {
        // Ensure the setting exists before returning history
        $this->showByKey($settingKey);

        return $this->settingRepo->historyForSetting($settingKey, $perPage);
    }
}
