<?php

namespace App\Http\Controllers\Api;

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
use App\Jobs\SendEmailJob;
use App\Models\Notifications;
use App\Traits\sendCpsNotification;
use App\Jobs\ActivateEmailJob;
use Illuminate\Support\Facades\Crypt;
use App\Api\Frontend\Modules\Project\Models\Project;
use App\Api\Frontend\Modules\Project\Models\Invitation;
use App\Api\Frontend\Modules\Account\Models\BusinessProfile;
use App\Services\LoginService;
use App\Services\DataSecurityService;

class LoginController extends BaseController
{
    use sendCpsNotification;

    protected $guard;
    protected $loginService;
    protected $dataSecurityService;

    public function __construct(LoginService $loginService, DataSecurityService $dataSecurityService)
    {
        $this->guard = "api";
        $this->loginService = $loginService;
        $this->dataSecurityService = $dataSecurityService;
    }
    /**
     * Handle the user signup request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userSignup(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/[A-Z]/',
                    'regex:/[@$!%*#?&]/'
                ],
                'confirm_password' => 'required|same:password'
            ], [
                'email.required' => 'Email is required',
                'email.email'    => 'Please provide a valid email address',
                'email.unique'   => 'This email is already registered',

                'password.required' => 'Password is required',
                'password.min'      => 'Password must be at least 8 characters',
                'password.regex'    => 'Password must contain at least one uppercase letter and one special character',

                'confirm_password.required' => 'Confirm Password is required',
                'confirm_password.same'     => 'Confirm Password must match the Password',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(), 400);
            }
            $name = trim($request->firstname . ' ' . $request->lastname);
            $user = User::create([
                'name'            => $name,
                'firstname'       => $request->firstname,
                'lastname'        => $request->lastname,
                'email'           => $request->email,
                'password'        => Hash::make($request->password),
                'address'         => $request->address ?? null,
                'phonenumber'     => $request->phonenumber ?? null,
                'activestatus'    => env('ACTIVE_STATUS'),
                'transpactstatus' => 0,
                // 'profileimage'    => 'assets/img/default-logo.png',
            ]);
            $role = Role::where('id', $request->account_type)->where('type', 1)->first();
            if ($role) {
                $user->assignRole($role->name);
            }
            if ($request->account_type == 3) {
                BusinessProfile::create(
                    [
                        'user_id'             => $user->id,
                        'businessname'        => $request->businessname ?? '',
                        'businesstype'        => $request->businesstype ?? null,
                        'address'             => $request->businessaddress ?? null,
                        'businessphone'       => $request->businessphone ?? null,
                        'company_registernum' => $request->company_registernum ?? null,
                    ]
                );
            }
            $baseUrl      = config('services.transpact_register_url');
            $appName      = config('app.name');
            $isTest       = env('IS_TEST');
            if ($isTest == true) {
                $transpactUrl       = "{$baseUrl}?RegEmail={$request->email}.test&co={$appName}";
            } else {
                $transpactUrl       = "{$baseUrl}?RegEmail={$request->email}&co={$appName}";
            }

            $this->handleProjectInvitations($user, $request, $user->name);

            $activeStatus = User::where('id', $user->id)->first();
            if ($activeStatus->activestatus == 0) {
                $activationCode = random_int(100000, 999999);
                $codeValidtime  = now();
                User::where('id', $user->id)->update([
                    'activation_code' => $activationCode,
                    'code_validtime'  => $codeValidtime,
                ]);
                $encryptedCode = Crypt::encrypt($activationCode);
                ActivateEmailJob::dispatch($request->email, $name, $encryptedCode, null, 'activate', 'Activation Link');
            }
            $claimsBlob = Crypt::encryptString(json_encode([
                'id'     => $user->id,
                'email'  => $user->email,
                'name'   => $user->name,
                'roleId' => $request->account_type ?? '',
            ]));
            $customClaims = [
                'encryptedData' => $claimsBlob,
                'iss'           => 'CPSAdmin',
                'iat'           => (int) now()->timestamp,
            ];
            $token    = JWTAuth::claims($customClaims)->fromUser($user);
            $userData = [
                'name'         => $user->name,
                'userId'       => $user->id,
                'email'        => $user->email,
                'role_name'    => $role->display_name ?? '',
                'roleId'       => $request->account_type ?? '',
                'token'        => $token,
                'expire_in'    => config('jwt.ttl') * 60,
                'profileImage' => $user->profileimage,
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

            if ($request->account_type == 2) {
                $type    = 'customer';
            } elseif ($request->account_type == 3) {
                $type = 'contractor';
            } else {
                $type = 'user';
            }
            ActivateEmailJob::dispatch($request->email, $name, null, null, $type, 'Welcome to CPS – Your Account Has Been Successfully Created!');

            $data = [
                'transpactUrl' => $transpactUrl,
                'userData'     => $this->dataSecurityService->encrypt($userData),
            ];

            return $this->sendResponse($data, 'Signup successful');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred.', ['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Handle the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userLogin(Request $request)
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
            $user = User::select('id', 'name', 'email', 'password', 'activestatus', 'transpactstatus', 'profileimage')->where('email', $request->username)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->sendError('Unauthorised.', ['error' => array('The username or password is incorrect.')], 400);
            }
            if ($user->activestatus != 1) {
                return $this->sendError('Account Not Activated.', ['error' => array('Please Activate your account')], 400);
            }
            $roles = ModelHasRoles::where('model_id', $user->id)->first();
            if ($roles->role_id == 1) {
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
                'id'    => $user->id,
                'email' => $user->email,
                'name'   => $user->name,
                'roleId' => $roleId ?? '',
            ]));
            $customClaims = [
                'encryptedData' => $claimsBlob,
                'iss'           => 'CPSAdmin',
                'iat'           => (int) now()->timestamp,
            ];
            $token    = JWTAuth::claims($customClaims)->fromUser($user);
            $userData = [
                'name'         => $user->name,
                'userId'       => $user->id,
                'email'        => $user->email,
                'role_name'    => $roleName->display_name ?? '',
                'roleId'       => $roleId ?? '',
                'token'        => $token,
                'expire_in'    => config('jwt.ttl') * 60,
                'profileImage' => $user->profileimage,
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
            return $this->sendResponse($encryptedUserData, 'Login successfully');
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
     * Get roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function getRoles(Request $request)
    {
        try {
            $roles = Role::where('type', 1)->get();
            return $this->sendResponse($roles, 'Role list');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred.', ['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Get Activation mail.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function activateAccount(Request $request)
    {
        try {
            $activationCode = Crypt::decrypt($request->code);
            $user           = User::where('activation_code', $activationCode)->first();

            $validUntil = Carbon::parse($user->code_validtime)->addHours(48);
            $now        = Carbon::now();

            if ($now->gt($validUntil)) {
                return $this->sendError('Activation code expired.', ['error' => array('Activation code expired')], 410);
            }
            if ($user->activestatus == 1) {
                return $this->sendResponse([], 'Account is already activated.');
            }

            $user->activestatus      = 1;
            $user->email_verified_at = now();
            $user->save();

            return $this->sendResponse([], 'Account activated successfully.');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred during activation.', ['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Handle the password change request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $response = $this->loginService->resetPassword($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
    public function updateProjectNotification($projectId, $notifyingUserType)
    {
        $project = Project::where('id', $projectId)
            ->select('customer_id', 'contractor_id', 'projectname', 'customer_email')
            ->first();

        if (!$project || !in_array($notifyingUserType, ['customer', 'contractor'])) {
            return;
        }

        $fromId = $notifyingUserType === 'customer' ? $project->contractor_id : $project->customer_id;
        $toId   = $notifyingUserType === 'customer' ? $project->customer_id   : $project->contractor_id;

        if ($notifyingUserType === 'customer') {
            $inviter = User::where('id', $project->contractor_id)->select('name')->first();
            $customMessage = "You have been invited by {$inviter->name} to join the '{$project->projectname}' project. Awaiting your approval.";
        } else {
            $customerEmail = $project->customer_email;
            $customMessage = "Your invited customer '{$customerEmail}' has successfully registered for the project '{$project->projectname}' in CPS.";
            $channelData = ['message' => $customMessage, 'forContractor' => $project->contractor_id, 'projectId' => $projectId];
            if (isset($channelData)) {
                $this->sendCpsNotification('invitechannel', $channelData, 0, 'broadcast');
            }
            // if (isset($channelData)) {
            //     broadcast(new \App\Events\InviteChannelBroadcast($channelData, 'invitechannel'));
            // }
        }

        Notifications::create([
            'from_id'    => $fromId,
            'to_id'      => $toId,
            'message'    => $customMessage,
            'isread'     => 0,
            'project_id' => $projectId,
        ]);
    }
    public function handleProjectInvitations($user, $request, $name = null)
    {
        if ($request->account_type != 2) {
            return;
        }

        $invitations = Invitation::where('invitemail', $request->email)
            ->select('id', 'project_id', 'invitefrom', 'inviteto')
            ->get();

        if ($invitations->isNotEmpty()) {
            foreach ($invitations as $invitation) {
                $project = Project::where('id', $invitation->project_id)
                    ->whereNull('customer_id')
                    ->first();

                if ($project) {
                    $project->update(['customer_id' => $user->id]);

                    Notifications::where('project_id', $project->id)
                        ->whereNull('to_id')
                        ->update(['to_id' => $user->id]);

                    $invitation->update(['inviteto' => $user->id]);
                    $this->updateProjectNotification($project->id, 'customer');
                    ActivateEmailJob::dispatch($request->email, $name, null, $project->id, 'project', 'Project Invitation');
                    $this->updateProjectNotification($project->id, 'contractor');
                }
            }
        }

        if (!empty($request->project_id)) {
            $projectId = Crypt::decrypt($request->project_id);

            Project::where('id', $projectId)->update([
                'customer_id' => $user->id,
            ]);

            Invitation::where('project_id', $projectId)->update([
                'inviteto' => $user->id,
            ]);

            Notifications::where('project_id', $projectId)
                ->whereNull('to_id')
                ->update(['to_id' => $user->id]);

            $this->updateProjectNotification($projectId, 'customer');
            ActivateEmailJob::dispatch($request->email, $name, null, $projectId, 'project', 'Project Invitation');
            $this->updateProjectNotification($projectId, 'contractor');
        }
    }
}
