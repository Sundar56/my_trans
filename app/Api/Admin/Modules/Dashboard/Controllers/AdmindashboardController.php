<?php

namespace App\Api\Admin\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Models\Modules;
use Illuminate\Support\Facades\DB;
use App\Api\Frontend\Modules\Project\Models\Project;

class AdmindashboardController extends BaseController
{
    /**
     * Display the dashboard with relevant statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $userCount       = DB::select('SELECT COUNT(*) AS userCount FROM users WHERE id != 1')[0]->userCount;
            $projectCount    = DB::select('SELECT COUNT(*) AS projectCount FROM projects WHERE is_create = 0')[0]->projectCount;
            $totalCommission = DB::select('SELECT SUM(admincommission) AS totalCommission FROM projects')[0]->totalCommission;
            $formatedAmount  = number_format($totalCommission, 2);

            $statistics = [
                'userCount'       => $userCount,
                'projectCount'    => $projectCount,
                'totalCommission' => $formatedAmount,
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
