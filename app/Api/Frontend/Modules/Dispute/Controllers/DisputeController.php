<?php

namespace App\Api\Frontend\Modules\Dispute\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use App\Services\DisputeService;

class DisputeController extends BaseController
{
    protected $disputeService;

    public function __construct(DisputeService $disputeService)
    {
        $this->disputeService    = $disputeService;
    }
    /**
     * Create Dispute.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createDispute(Request $request)
    {
        $response = $this->disputeService->createDispute($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
    /**
     * View Dispute.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewDispute(Request $request)
    {
        $response = $this->disputeService->viewDispute($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
    /**
     * Get Dispute list.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDisputeList(Request $request)
    {
        $response = $this->disputeService->getDisputeList($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
    /**
     * Create Dispute chat.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function disputeChat(Request $request)
    {
        $response = $this->disputeService->disputeChat($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
}
