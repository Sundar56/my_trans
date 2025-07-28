<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendEmailJob;
use Illuminate\Support\Str;
use App\Models\LoginHistory;
use App\Traits\projectData;


class LoginService
{
    use projectData;

    /**
     * Handle the Forgot Password request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|email|exists:users,email',
            ], [
                'username.exists' => 'User not found',
                'username.email'  => 'Must be Valid Email',
            ]);

            if ($validator->fails()) {
                return $this->handleValidationFailure($validator);
            }
            $user           = User::where('email', $request->username)->first();
            $newPassword    = Str::random(10);
            $user->password = Hash::make($newPassword);
            $user->save();

            SendEmailJob::dispatch($user->email, $newPassword, $user->name, 'Reset Password');

            return $this->returnSuccess(null, 'Password reset email sent');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Handle the logout request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logOut(Request $request)
    {
        try {
            $userId       = $request->attributes->get('decoded_token')->get('id');
            $logoutTime   = now();
            $loginHistory = LoginHistory::where('user_id', $userId)
                ->where('logouttime', null)->latest()->first();

            if ($loginHistory && ($loginHistory->logouttime === null || $loginHistory->logouttime === '')) {
                $loginHistory->logouttime = $logoutTime;
                $loginHistory->duration   = DB::raw("TIMESTAMPDIFF(SECOND, logintime, '$logoutTime')");
                $loginHistory->save();
            }
            JWTAuth::invalidate(JWTAuth::getToken());

            return $this->returnSuccess(null, 'Successfully logged out');
        } catch (\Exception $e) {
            return $this->handleException($e);
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
        try {
            $validator = Validator::make($request->all(), [
                'old_password' => 'required',
                'new_password' => 'required',
                'confirm_password' => ['same:new_password'],
            ]);
            if ($validator->fails()) {
               return $this->handleValidationFailure($validator);
            }

            $oldPassword = $request->old_password;
            $newPassword = $request->new_password;

            $userId = $request->attributes->get('decoded_token')->get('id');
            $user = User::where('id', $userId)->first();

            if (!Hash::check($oldPassword, $user->password)) {
                return $this->returnError("Old Password Doesn't match!", 400);
            }

            if (strcmp($oldPassword, $newPassword) == 0) {
                return $this->returnError("New Password cannot be same as your current password", 400);
            }

            $user->password = $newPassword;
            $user->save();

            return $this->returnSuccess(null, 'Password changed successfully!');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
