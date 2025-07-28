<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Traits\projectData;
use App\Api\Frontend\Modules\Transpact\Models\EscrowSettings;
use App\Api\Frontend\Modules\Transpact\Models\EscrowUpdateHistory;
use App\Services\DataSecurityService;

class AdminSettingService
{
    use projectData;

    protected $dataSecurityService;

    public function __construct(DataSecurityService $dataSecurityService)
    {
        $this->dataSecurityService = $dataSecurityService;
    }
    /**
     * 
     * View Admin settings .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function viewAdminSettings(Request $request)
    {
        try {
            $settingName   = env('ESCROW_SETTING_NAME');
            $adminSettings = EscrowSettings::where('name', $settingName)->first();
            $data = $this->dataSecurityService->encrypt($adminSettings);

            return $this->returnSuccess($data, 'View Admin Settings');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * 
     * Update Admin settings .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateAdminSettings(Request $request)
    {
        try {
            $userId            = $request->attributes->get('decoded_token')->get('id');
            $transpactUsername = $request->username ?? '';
            $transpactPassword = $request->password ?? '';
            $soapUrl           = $request->soapurl ?? '';
            $webhookUrl        = $request->weburl ?? '';
            $description       = $request->description ?? '';
            $deductionFeeRaw   = $request->deductionfee ?? '';
            $deductionFee      = json_decode($deductionFeeRaw, true);

            $settingName        = env('ESCROW_SETTING_NAME');
            $escrowSetting      = EscrowSettings::where('name', $settingName)->first();
            $previousRecordJson = $escrowSetting ? json_encode($escrowSetting->toArray()) : json_encode([]);

            $escrowSetting->update([
                'username'     => $transpactUsername,
                'password'     => $transpactPassword,
                'soapurl'      => $soapUrl,
                'webhookurl'   => $webhookUrl,
                'description'  => $description,
                'deductionfee' => $deductionFee,
            ]);

            $updatedRecordJson = json_encode($escrowSetting->fresh()->toArray());
            $this->escrowUpdateHistory($request, $userId, $previousRecordJson, $updatedRecordJson);

            return $this->returnSuccess(null, 'Admin Setting Updated');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    private function escrowUpdateHistory($request, $userId, $previousRecordJson, $updatedRecordJson)
    {
        EscrowUpdateHistory::create([
            'updated_by'      => $userId,
            'previous_record' => $previousRecordJson,
            'updated_record'  => $updatedRecordJson,
            'updated_time'    => now(),
            'ipaddress'       => $request->ip(),
            'useragent'       => $request->userAgent()
        ]);
    }
}
