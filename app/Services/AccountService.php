<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\ModelHasRoles;
use App\Traits\projectData;
use Illuminate\Support\Facades\Hash;
use App\Services\UploadFileService;
use App\Api\Frontend\Modules\Account\Models\BusinessProfile;

class AccountService
{
    use projectData;

    protected $uploadFileService;

    public function __construct(UploadFileService $uploadFileService)
    {
        $this->uploadFileService = $uploadFileService;
    }
    /**
     * Get details of a specific user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function viewMyProfile($userId)
    {
        try {
            $myProfile = User::leftJoin('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->select('users.*', 'model_has_roles.role_id as role_id', 'roles.name as role_name')
                ->where('users.id', $userId)
                ->first();

            $businessProfile = BusinessProfile::where('user_id', $userId)->first();
            $data = collect($myProfile)
                ->merge($businessProfile ? $businessProfile->toArray() : [])
                ->toArray();
            if ($businessProfile) {
                $businessTypes = config('custom.businesstype');
                $data['typeOfBusiness'] = $businessTypes[$businessProfile->businesstype];
            } else {
                $data['typeOfBusiness'] = [];
            }
            if (!$data) {
                return $this->returnError('User not found', 404);
            }
            return $this->returnSuccess($data, 'My Profile Details');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * update My profile request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMyProfile(Request $request, $userId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email,' . $userId . ',id',
            ], [
                'email.required' => 'Email is required',
                'email.email'    => 'Please provide a valid email address',
            ]);
            if ($validator->fails()) {
               return $this->handleValidationFailure($validator);
            }
            $name   = trim($request->firstname . ' ' . $request->lastname);
            $roleId = $request->account_type;
            $updateUser  = User::where('id', $userId)->update([
                'name'         => $name,
                'firstname'    => $request->firstname,
                'lastname'     => $request->lastname,
                'email'        => $request->email,
                'address'      => $request->address ?? null,
                'phonenumber'  => $request->phonenumber ?? null,
                'activestatus' => $request->activestatus ?? null,
            ]);
            if ($roleId) {
                $updateRole = ModelHasRoles::where('model_id', $userId)
                    ->update(['role_id' => $roleId]);
                if ($updateUser === 0 && $updateRole === 0) {
                    return [
                        'status'     => false,
                        'message'    => 'No changes were made.',
                        'statusCode' => 200,
                    ];
                }
            }
            if ($roleId == 3) {
                BusinessProfile::where('user_id', $userId)->update(
                    [
                        'user_id'             => $userId,
                        'businessname'        => $request->businessname ?? '',
                        'businesstype'        => $request->businesstype ?? null,
                        'address'             => $request->businessaddress ?? null,
                        'businessphone'       => $request->businessphone ?? null,
                        'company_registernum' => $request->company_registernum ?? null,
                    ]
                );
            }
            $oldPassword    = $request->password;
            $newPassword    = $request->new_password;
            $repeatPassword = $request->confirm_password;
            if ($oldPassword && $newPassword) {
                $user        = User::where('id', $userId)->first();
                if ($newPassword  != $repeatPassword) {
                    return $this->returnError("Repeat Password doesn't match to New Password", 400);
                }
                if (!Hash::check($oldPassword, $user->password)) {

                    return $this->returnError("Old Password Doesn't match!", 400);
                }
                if (strcmp($oldPassword, $newPassword) == 0) {
                    return $this->returnError("New Password cannot be same as your current password", 400);
                }
                $user->password = $newPassword;
                $user->save();
            }
            $response = $this->uploadFileService->uploadProfileImage($request, $userId, $roleId);
            if (isset($response['status']) && $response['status'] === false) {
                return $this->returnError($response['message'], 404);
            }
            $data = User::leftJoin('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->select('users.*', 'model_has_roles.role_id as role_id', 'roles.display_name as role_name')
                ->where('users.id', $userId)
                ->first();
            if ($data) {
                $data->roleId          = $data->role_id;
                $data->userId          = $data->id;
                $data->profileImage    = $data->profileimage;
                $data->transpactStatus = $data->transpactstatus;
            }
            $businessData = BusinessProfile::where('user_id', $userId)->first();

            return $this->returnSuccess([
                'user'            => $data,
                'businessProfile' => $roleId == 3 ? $businessData : null,
            ], 'Profile updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
