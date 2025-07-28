<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Models\User;
use App\Traits\projectData;
use App\Models\UserSignHistory;
use App\Api\Frontend\Modules\Project\Models\Project;
use App\Api\Frontend\Modules\Dispute\Models\Dispute;
use App\Api\Frontend\Modules\Dispute\Models\DisputeFiles;
use App\Api\Frontend\Modules\Project\Models\ProjectContract;
use App\Api\Frontend\Modules\Dispute\Models\ChatAttachment;

class UploadFileService
{
    use projectData;
    
    /**
     * Upload Profile image.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProfileImage(Request $request, $userId, $roleId)
    {
        $validator = Validator::make($request->all(), [
            'profileimage' => 'file|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return [
                'status'     => false,
                'message'    => 'Invalid image file.',
                'errors'     => $validator->errors(),
                'statusCode' => 400
            ];
        }
        switch ($roleId) {
            case 2:
                $path = "/uploadassets/customers/{$userId}/";
                break;

            case 3:
                $path = "/uploadassets/contractors/{$userId}/";
                break;

            case 1:
                $path = "/uploadassets/contractors/admin/";
                break;

            default:
                $path = "/uploadassets/users/{$userId}/";
                break;
        }
        if (!File::exists(public_path($path))) {
            File::makeDirectory(public_path($path), 0775, true);
        }

        if ($request->hasFile('profileimage')) {
            $file     = $request->file('profileimage');
            $filePath = $this->uploadFile($file, $path, 'profile_');
            User::where('id', $userId)->update([
                'profileimage' => $filePath
            ]);
        }
    }
    private function uploadFile($file, $path, $prefix)
    {
        $fileName = $prefix . time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path($path), $fileName);
        return $path . $fileName;
    }
    /**
     * Upload Project contract Files.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProjectContract(Request $request, $userId, $projectId)
    {
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png'
        ];

        $path = "/uploadassets/contractors/{$userId}/{$projectId}/";

        if (!File::exists(public_path($path))) {
            File::makeDirectory(public_path($path), 0775, true);
        }

        if ($request->hasFile('projectcontract')) {
            $files = $request->file('projectcontract');
            $data = [];

            foreach ($files as $file) {
                if (in_array($file->getMimeType(), $allowedMimeTypes)) {
                    $filePath = $this->uploadFiles($file, $path, 'contract_');
                    $data[] = [
                        'user_id'    => $userId,
                        'project_id' => $projectId,
                        'contract'   => $filePath,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } else {
                    return [
                        'status'     => false,
                        'message'    => 'Invalid file type: ' . $file->getClientOriginalName(),
                        'errors'     => ["error" => ['Invalid file type: ' . $file->getClientOriginalName(),]],
                        'statusCode' => 400
                    ];
                }
            }

            ProjectContract::insert($data);
        }
    }

    protected function uploadFiles($file, $path, $prefix = 'file_')
    {
        $uniqueName = $prefix . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path($path), $uniqueName);
        return $path . $uniqueName;
    }
    /**
     * Upload Dispute support files.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDisputeFiles(Request $request, $userId, $roleId, $disputeId)
    {
        switch ($roleId) {
            case 2:
                $path = "/uploadassets/dispute/customer/{$userId}/";
                break;

            case 3:
                $path = "/uploadassets/dispute/contractor/{$userId}/";
                break;

            case 1:
                $path = "/uploadassets/dispute/admin/";
                break;

            default:
                $path = "/uploadassets/users/{$userId}/";
                break;
        }
        if (!File::exists(public_path($path))) {
            File::makeDirectory(public_path($path), 0775, true);
        }
        if ($request->hasFile('support_material')) {
            $files = $request->file('support_material');
            $data = [];
            foreach ($files as $file) {
                $filePath = $this->uploadFiles($file, $path, 'dispute_');
                $data[] = [
                    'dispute_id'       => $disputeId,
                    'support_material' => $filePath,
                ];
            }
            DisputeFiles::insert($data);
        }
    }
    /**
     * Upload Dispute chat support files.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadChatAttachment(Request $request, $userId, $roleId, $disputeChatId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'filename.*' => 'file|max:2048',
            ], [
                'filename.*.max' => 'Each file must not exceed 2MB.',
            ]);
            if ($validator->fails()) {
                return $this->handleValidationFailure($validator);
            }

            $files     = $request->file('filename');
            if (!is_array($files)) {
                return [
                    'status'     => true,
                    'message'    => 'No files uploaded or incorrect format.',
                    'errors'     => ['filename' => ['Please upload at least one valid file.']],
                    'statusCode' => 200
                ];
            }
            $totalSize = 0;
            foreach ($files as $file) {
                $totalSize += $file->getSize();
            }

            if ($totalSize > (10 * 1024 * 1024)) {
                return [
                    'status'     => false,
                    'message'    => 'Total file size must not exceed 10MB',
                    'errors'     => ["error" => ['Total file size must not exceed 10MB']],
                    'statusCode' => 422
                ];
            }
            switch ($roleId) {
                case 2:
                    $path = "/uploadassets/disputechat/customer/{$userId}/{$disputeChatId}/";
                    break;

                case 3:
                    $path = "/uploadassets/disputechat/contractor/{$userId}/{$disputeChatId}/";
                    break;

                case 1:
                    $path = "/uploadassets/disputechat/admin/{$disputeChatId}/";
                    break;

                default:
                    $path = "/uploadassets/disputechat/{$userId}/{$disputeChatId}/";
                    break;
            }
            if (!File::exists(public_path($path))) {
                File::makeDirectory(public_path($path), 0775, true);
            }
            if ($request->hasFile('filename')) {
                $files = $request->file('filename');
                $data = [];
                foreach ($files as $file) {
                    $filePath = $this->uploadFiles($file, $path, 'disputechat_');
                    $data[] = [
                        'disputechat_id' => $disputeChatId,
                        'filename'       => $filePath,
                        'filetype'       => $file->getClientOriginalExtension(),
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }
                ChatAttachment::insert($data);
            }
        } catch (\Exception $e) {
            return [
                'status'     => false,
                'message'    => 'An error occurred.',
                'errors'     => ['error' => $e->getMessage()],
                'statusCode' => 500,
            ];
        }
    }
    /**
     * Upload Profile image.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadSignature(Request $request, $userId, $roleId, $projectId)
    {
        $validator = Validator::make($request->all(), [
            'filename.*' => 'regex:/^data:image\/(png|jpeg|jpg);base64,/',
        ]);

        if ($validator->fails()) {
            return ([
                'status'     => false,
                'message'    => 'Signature Required',
                'errors'     => $validator->errors(),
                'statusCode' => 400
            ]);
        }
        switch ($roleId) {
            case 2:
                $path = "/uploadassets/customers/signature/{$projectId}/";
                break;

            case 3:
                $path = "/uploadassets/contractors/signature/{$projectId}/";
                break;

            default:
                $path = "/uploadassets/users/signature/{$projectId}/";
                break;
        }
        if (!File::exists(public_path($path))) {
            File::makeDirectory(public_path($path), 0775, true);
        }

        $signatureBase64   = $request->input('usersign');
        list($type, $data) = explode(';', $signatureBase64);
        list(, $data)      = explode(',', $data);
        $imageData         = base64_decode($data);
        $filename          = 'sign_' . time() . '.png';

        file_put_contents(public_path($path . $filename), $imageData);

        if ($roleId == 2) {
            Project::where('id', $projectId)->update([
                'customer_sign'   => $path . $filename ?? null,
            ]);
        }
        if ($roleId == 3) {
            Project::where('id', $projectId)->update([
                'contractor_sign' => $path . $filename ?? null,
            ]);
        }

        UserSignHistory::create([
            'userid'      => $userId,
            'updatedsign' => $path . $filename
        ]);
    }
}
