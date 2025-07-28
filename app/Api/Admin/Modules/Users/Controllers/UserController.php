<?php

namespace App\Api\Admin\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Services\AccountService;
use Spatie\Permission\Models\Role;

class UserController extends BaseController
{
    protected $userService;
    protected $accountService;

    public function __construct(UserService $userService,AccountService $accountService)
    {
        $this->userService = $userService;
        $this->accountService = $accountService;
    }
    /**
     * View My profile request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function usersList(Request $request)
    {
        $response = $this->userService->usersList($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
     /**
     * Get details of a specific user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewUser(Request $request)
    {
        $userId = $request->user_id;

        if (!$userId) {
            return $this->sendError('User ID is required.', [], 400);
        }
        $response = $this->accountService->viewMyProfile($userId);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
         /**
     * Get details of a specific user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUser(Request $request)
    {
        $userId = $request->user_id;
        if (!$userId) {
            return $this->sendError('User ID is required.', [], 400);
        }
        $response = $this->accountService->updateMyProfile($request, $userId);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
        /**
     * View My profile request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function veiwAdminProfile(Request $request)
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
    public function updateAdminProfile(Request $request)
    {
        $userId = $request->attributes->get('decoded_token')->get('id');
        $response = $this->accountService->updateMyProfile($request,$userId);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
    /**
     * Create new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createUsers(Request $request)
    {
        $response = $this->userService->createUsers($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
        /**
     * Get roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function getUsersRole(Request $request)
    {
        try {
            $roles = Role::where('name','!=' ,'superadmin')->get();
            return $this->sendResponse($roles, 'Roles list');

        } catch (\Exception $e) {
            return $this->sendError('An error occurred.', ['error' => $e->getMessage()], 500);
        }
    }
}
