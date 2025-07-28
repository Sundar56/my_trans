<?php

namespace App\Api\Frontend\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use App\Services\AccountService;

class AccountController extends BaseController
{
    protected $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService    = $accountService;
    }
    /**
     * View My profile request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewMyProfile(Request $request)
    {
        $userId = $request->attributes->get('decoded_token')->get('id');
        $response = $this->accountService->viewMyProfile($userId);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
     /**
     * update my profile request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMyProfile(Request $request)
    {
        $userId = $request->attributes->get('decoded_token')->get('id');
        $response = $this->accountService->updateMyProfile($request,$userId);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
}
