<?php

namespace App\Api\Admin\Modules\Projects\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Services\ProjectService;

class TotalProjectController extends BaseController
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }
    /**
     * Get Project list.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjectList(Request $request)
    {
        $response = $this->projectService->getProjectListNew($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
    /**
     * View new projects.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewProject(Request $request)
    {
        $response = $this->projectService->viewProjectNew($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
    /**
     * Update projects.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProject(Request $request)
    {
        $response = $this->projectService->updateProject($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
    /**
     * Task update.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTasksbyAdmin(Request $request)
    {
        $response = $this->projectService->updateTasks($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
}
