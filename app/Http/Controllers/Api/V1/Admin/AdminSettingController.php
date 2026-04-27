<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Http\Resources\Admin\ConfigurationHistoryResource;
use App\Http\Resources\Admin\SystemSettingResource;
use App\Models\SystemSetting;
use App\Services\Admin\AdminSettingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    public function __construct(
        private readonly AdminSettingService $settingService,
    ) {}

    // GET /api/v1/admin/settings

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('administrate', SystemSetting::class);

        $settings = $this->settingService->list(
            filters: $request->only(['search', 'setting_type', 'is_public']),
            perPage: min((int) $request->input('per_page', 15), 50),
        );

        return response()->json([
            'status' => 'success',
            'data'   => SystemSettingResource::collection($settings->items()),
            'meta'   => [
                'current_page' => $settings->currentPage(),
                'last_page'    => $settings->lastPage(),
                'per_page'     => $settings->perPage(),
                'total'        => $settings->total(),
                'from'         => $settings->firstItem(),
                'to'           => $settings->lastItem(),
            ],
            'links'  => [
                'first' => $settings->url(1),
                'last'  => $settings->url($settings->lastPage()),
                'prev'  => $settings->previousPageUrl(),
                'next'  => $settings->nextPageUrl(),
            ],
        ]);
    }

    // GET /api/v1/admin/settings/{key}

    /**
     * @throws AuthorizationException
     */
    public function show(string $key): JsonResponse
    {
        $this->authorize('administrate', SystemSetting::class);

        $setting = $this->settingService->showByKey($key);

        return response()->json([
            'status' => 'success',
            'data'   => new SystemSettingResource($setting),
        ]);
    }

    // PATCH /api/v1/admin/settings/{key}

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateSettingRequest $request, string $key): JsonResponse
    {
        $this->authorize('administrate', SystemSetting::class);

        $setting = $this->settingService->showByKey($key);
        $updated = $this->settingService->update(
            setting: $setting,
            dto:     $request->getDto(),
            admin:   $request->user(),
            ip:      $request->ip(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Setting updated successfully.',
            'data'    => new SystemSettingResource($updated),
        ]);
    }

    // GET /api/v1/admin/settings/{key}/history

    /**
     * @throws AuthorizationException
     */
    public function history(Request $request, string $key): JsonResponse
    {
        $this->authorize('administrate', SystemSetting::class);

        $history = $this->settingService->history(
            settingKey: $key,
            perPage:    min((int) $request->input('per_page', 15), 50),
        );

        return response()->json([
            'status' => 'success',
            'data'   => ConfigurationHistoryResource::collection($history->items()),
            'meta'   => [
                'current_page' => $history->currentPage(),
                'last_page'    => $history->lastPage(),
                'per_page'     => $history->perPage(),
                'total'        => $history->total(),
                'from'         => $history->firstItem(),
                'to'           => $history->lastItem(),
            ],
            'links'  => [
                'first' => $history->url(1),
                'last'  => $history->url($history->lastPage()),
                'prev'  => $history->previousPageUrl(),
                'next'  => $history->nextPageUrl(),
            ],
        ]);
    }
}
