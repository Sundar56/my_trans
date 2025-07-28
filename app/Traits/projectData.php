<?php

namespace App\Traits;

use App\Api\Frontend\Modules\Project\Models\Project;
use Illuminate\Support\Facades\Crypt;

trait projectData
{
    protected function getProjectWithRelations($projectId)
    {
        $project = Project::leftJoin('users as contractor', 'projects.contractor_id', '=', 'contractor.id')
            ->leftJoin('businessprofile as contractor_profile', 'projects.contractor_id', '=', 'contractor_profile.user_id')
            ->leftJoin('users as customer', 'projects.customer_id', '=', 'customer.id')
            ->where('projects.id', $projectId)
            ->select(
                'projects.*',
                'contractor.email as contractor_email',
                'contractor.name as contractor_name',
                'contractor.signature as contractor_sign',
                'contractor_profile.businessname as contractor_businessname',
                'contractor_profile.company_registernum as contractor_company_registernum',
                'contractor_profile.businessphone as contractor_businessphone',
                'contractor_profile.businessimage as contractor_businessimage',
                'contractor_profile.businesstype as contractor_businesstype',
                'contractor_profile.address as contractor_address',
                'customer.email as customerEmail',
                'customer.name as customerName',
                'customer.address as customer_address',
                'customer.phonenumber as customer_phone',
                'customer.signature as customer_sign',
            )
            ->first();

        return $project;
    }
    public function handleException(\Throwable $e, int $statusCode = 500, string $message = 'An error occurred.')
    {
        return [
            'status'     => false,
            'message'    => $message,
            'errors'     => ['error' => $e->getMessage()],
            'statusCode' => $statusCode,
        ];
    }
    public function returnSuccess($data = [], $message = 'Success', $statusCode = 200)
    {
        return [
            'status'     => true,
            'data'       => $data,
            'message'    => $message,
            'statusCode' => $statusCode,
        ];
    }
    public function returnError($message = 'Error', $statusCode = 400)
    {
        return [
            'status'     => false,
            'message'    => $message,
            'errors'     => ['error' => [$message]],
            'statusCode' => 400,
        ];
    }
    protected function decryptProjectId($encryptedId)
    {
        try {
            return Crypt::decrypt($encryptedId);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return $encryptedId;
        }
    }
    protected function handleValidationFailure($validator)
    {
        return [
            'status'     => false,
            'message'    => 'Validation Error.',
            'errors'     => $validator->errors(),
            'statusCode' => 400
        ];
    }
}
