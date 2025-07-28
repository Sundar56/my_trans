<?php

namespace App\Api\Admin\Modules\Settings\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Services\AdminSettingService;

class AdminSettingsController extends BaseController
{
    protected $adminSettingService;

    public function __construct(AdminSettingService $adminSettingService)
    {
        $this->adminSettingService = $adminSettingService;
    }
    /**
     * View Admin settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewAdminSettings(Request $request)
    {
        $response = $this->adminSettingService->viewAdminSettings($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
    /**
     * Update Admin settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAdminSettings(Request $request)
    {
        $response = $this->adminSettingService->updateAdminSettings($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
}
