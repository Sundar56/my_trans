<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\ModelHasRoles;
use App\Jobs\ActivateEmailJob;
use App\Traits\projectData;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Role;
use App\Jobs\SendEmailJob;
use App\Api\Frontend\Modules\Account\Models\BusinessProfile;

class UserService
{
    use projectData;

    /**
     * Get details of a users list.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function usersList(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'page'   => 'sometimes|integer|min:1',
                'length' => 'sometimes|integer|min:1|max:500',
            ]);
            if ($validator->fails()) {
               return $this->handleValidationFailure($validator);
            }

            $page     = $request->input('page') ?: '1';
            $perPage  = $request->input('length') ?: env("TABLE_LIST_LENGTH");
            $userId   = $request->attributes->get('decoded_token')->get('id');
            $userRole = ModelHasRoles::where('model_id', $userId)->first();
            $userType = $request->usertype ?: 0;

            $dbdata = User::leftJoin('model_has_roles as modelhasroles', 'modelhasroles.model_id', '=', 'users.id')
                ->leftJoin('roles', 'roles.id', '=', 'modelhasroles.role_id')
                ->select('users.*', 'roles.display_name as rolename');
            if ($userType > 0) {
                $dbdata->where('modelhasroles.role_id', $userType);
            } else {
                $dbdata->where('modelhasroles.role_id', '!=', 1);
            }


            if ($request->filled('search')) {
                $search = $request->input('search');
                $columns = ['users.name', 'roles.display_name', 'users.created_at'];

                $dbdata->where(function ($query) use ($search, $columns) {
                    foreach ($columns as $index => $column) {
                        if ($index === 0) {
                            $query->where($column, 'like', '%' . $search . '%');
                        } else {
                            $query->orWhere($column, 'like', '%' . $search . '%');
                        }
                    }
                });
            }

            $orderColumn    = $request->input('order_column') ?: 'users.created_at';
            $orderDirection = $request->input('order_dir') ?: 'desc';

            $paginatedData = $dbdata->orderBy($orderColumn, $orderDirection)
                ->paginate($perPage, ['*'], 'page', $page);

            return $this->returnSuccess([
                'list'         => $paginatedData->items(),
                'currentPage'  => $paginatedData->currentPage(),
                'totalPages'   => $paginatedData->lastPage(),
                'recordsTotal' => $paginatedData->total(),
            ], 'Users list.');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Handle the user signup request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createUsers(Request $request)
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
                return $this->handleValidationFailure($validator);
            }
            $name = trim($request->firstname . ' ' . $request->lastname);
            $user = User::create([
                'name'         => $name,
                'firstname'    => $request->firstname,
                'lastname'     => $request->lastname,
                'email'        => $request->email,
                'password'     => Hash::make($request->password),
                'address'      => $request->address ?? null,
                'phonenumber'  => $request->phonenumber ?? null,
                'activestatus' => $request->activestatus ?? null,
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
            SendEmailJob::dispatch($user->email, $request->password, $user->name, 'New User Password');

            $activationCode = random_int(100000, 999999);
            $codeValidtime  = now();
            User::where('id', $user->id)->update([
                'activation_code' => $activationCode,
                'code_validtime'  => $codeValidtime,
            ]);
            $encryptedCode = Crypt::encrypt($activationCode);
            ActivateEmailJob::dispatch($request->email, $name, $encryptedCode);

            return $this->returnSuccess(null, 'User created successfully!');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
