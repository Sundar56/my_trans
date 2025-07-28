<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Models\Modules;
use App\Api\Frontend\Modules\Dispute\Models\Dispute;
use App\Api\Frontend\Modules\Project\Models\Project;

class DashboardController extends BaseController
{
    /**
     * Display the dashboard with relevant statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $userId = $request->attributes->get('decoded_token')->get('id');
            $roleId = $request->attributes->get('decoded_token')->get('roleId');
            if ($roleId == 2) {
                $projectCount = Project::where('customer_id', $userId)->where('is_create', 0)->count();
            }
            if ($roleId == 3) {
                $projectCount = Project::where('contractor_id', $userId)->where('is_create', 0)->count();
            }
            $disputeCount = Dispute::where('created_by', $userId)->count();
            
            $statistics = [
                'projectCount'    => $projectCount,
                'disputeCount'    => $disputeCount,
            ];

            return $this->sendResponse($statistics, 'Dashboard statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while fetching dashboard statistics.', ['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Display the dashboard with relevant modules.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function modulesList(Request $request)
    {
        try {
            $modules = Modules::where('type', 0)->get();

            return $this->sendResponse($modules, 'Modules list');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while fetching dashboard statistics.', ['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Display the dashboard with relevant modules.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminModulesList(Request $request)
    {
        try {
            $modules = Modules::where('type', 1)->get();

            return $this->sendResponse($modules, 'Admin Modules list');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while fetching dashboard statistics.', ['error' => $e->getMessage()], 500);
        }
    }
}
