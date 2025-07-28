<?php

namespace App\Api\Admin\Modules\Adminlogin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginHistory;
use Illuminate\Support\Carbon;
use App\Models\ModelHasRoles;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\Services\LoginService;
use App\Services\DataSecurityService;

class AdminloginController extends BaseController
{
    protected $loginService;
    protected $dataSecurityService;

    public function __construct(LoginService $loginService, DataSecurityService $dataSecurityService)
    {
        $this->loginService = $loginService;
        $this->dataSecurityService = $dataSecurityService;
    }
    /**
     * Handle the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminLogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(), 400);
            }
            // $fieldType = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
            $user = User::select('id', 'name', 'email', 'password')->where('email', $request->username)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->sendError('Unauthorised.', ['error' => array('The username or password is incorrect.')], 400);
            }

            $roles = ModelHasRoles::where('model_id', $user->id)->first();
            if ($roles->role_id != 1) {
                return $this->sendError('Unauthorised.', ['error' => array('Login access denied')], 400);
            }
            if ($roles) {
                $roleId   = $roles->role_id;
                $roleName = Role::select('display_name')->where('id', $roleId)->first();
            }
            User::where('id', $user->id)->update([
                'lastlogin' => now(),
            ]);
            $claimsBlob = Crypt::encryptString(json_encode([
                'id'     => $user->id,
                'email'  => $user->email,
                'name'   => $user->name,
                'roleId' => $roleId ?? '',
            ]));
            $customClaims = [
                'encryptedData' => $claimsBlob,
                'iss'           => 'CPS',
                'iat'           => (int) now()->timestamp,
            ];
            $token    = JWTAuth::claims($customClaims)->fromUser($user);
            $userData = [
                'name'      => $user->name,
                'userId'    => $user->id,
                'email'     => $user->email,
                'role_name' => $roleName->display_name ?? '',
                'roleId'    => $roleId ?? '',
                'token'     => $token,
                'expire_in' => config('jwt.ttl') * 60
            ];

            $lastRecord = LoginHistory::where('user_id', $user->id)->latest()->first();

            if ($lastRecord && !$lastRecord->logouttime) {
                $tokenExpiry = config('jwt.ttl');
                $loginTime   = Carbon::parse($lastRecord->logintime);
                $logoutTime  = $loginTime->addMinutes($tokenExpiry);

                $lastRecord->logouttime = $logoutTime;
                $lastRecord->duration   = DB::raw("TIMESTAMPDIFF(SECOND, logintime, '$logoutTime')");
                $lastRecord->save();
            }

            $loginTime = now();
            LoginHistory::create([
                'user_id'   => $user->id,
                'logintime' => $loginTime,
                'ipaddress' => $request->ip(),
                'useragent' => $request->userAgent()
            ]);
            $encryptedUserData = $this->dataSecurityService->encrypt($userData);
            return $this->sendResponse($encryptedUserData, 'Login successfully by Admin');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred.', ['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Handle the Forgot Password request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        $response = $this->loginService->forgotPassword($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
    /**
     * Handle the logout request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logOut(Request $request)
    {
        $response = $this->loginService->logOut($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
    /**
     * Handle the password change request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminResetPassword(Request $request)
    {
        $response = $this->loginService->resetPassword($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
    /**
     * Handle the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activateUser(Request $request)
    {
        try {
            $userId = $request->user_id;
            if (empty($userId)) {
                return [
                    'status'     => false,
                    'message'    => 'User not Found',
                    'errors'     => ['error' => 'User not Found'],
                    'statusCode' => 404
                ];
            }
            $activateStatus = User::where('id', $userId)->value('activestatus');
            $userActivate   = ($activateStatus == 1) ? 0 : 1;
            User::where('id', $userId)->update([
                'activestatus' => $userActivate,
            ]);
            return [
                'status'     => true,
                'message'    => 'User Active status changed',
                'data'       => null,
                'statusCode' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status'     => false,
                'message'    => 'An error occurred while creating the user',
                'errors'     => ['error' => $e->getMessage()],
                'statusCode' => 500
            ];
        }
    }
}
