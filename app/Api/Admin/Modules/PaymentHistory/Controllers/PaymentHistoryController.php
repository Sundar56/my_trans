<?php

namespace App\Api\Admin\Modules\PaymentHistory\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Services\PaymentHistoryService;
use App\Services\TranspactService;

class PaymentHistoryController extends BaseController
{
    protected $paymentHistoryService;
    protected $transpactService;

    public function __construct(PaymentHistoryService $paymentHistoryService, TranspactService $transpactService)
    {
        $this->paymentHistoryService = $paymentHistoryService;
        $this->transpactService      = $transpactService;
    }
   /**
     * Get Payment History list.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentHistoryList(Request $request)
    {
        $response = $this->paymentHistoryService->getPaymentHistoryList($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
       /**
     * Get Payment History list.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminTranspactHistory(Request $request)
    {
        $response = $this->transpactService->viewAdminTranspactHistory($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
}
