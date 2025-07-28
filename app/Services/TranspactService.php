<?php

namespace App\Services;

use Illuminate\Http\Request;
use SoapClient;
use SoapHeader;
use SoapFault;
use Artisaninweb\SoapWrapper\SoapWrapper;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use App\Traits\sendCpsNotification;
use App\Traits\projectData;
use App\Services\ProjectService;
use App\Jobs\ProjectStatusJob;
use App\Api\Frontend\Modules\Project\Models\Project;
use App\Api\Frontend\Modules\Project\Models\ProjectTasks;
use App\Api\Frontend\Modules\Transpact\Models\Transpact;
use App\Api\Frontend\Modules\Transpact\Models\ApiHistory;
use App\Api\Frontend\Modules\Transpact\Models\EscrowSettings;
use Illuminate\Contracts\Encryption\DecryptException;

class TranspactService
{
    use sendCpsNotification, projectData;

    protected $client;
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;

        $wsdl = env('TRANSPACT_URL');
        $this->client = new SoapClient($wsdl, [
            'trace'      => true,
            'exceptions' => true,
        ]);
    }

    protected function callSoap(string $method, array $params, $projectId = null, $userId)
    {
        try {
            $response  = $this->client->__soapCall($method, [$params]);
            // dd($response);
            $this->storeApiHistory($response, $userId, $method, $params);

            if ($method === 'CreateTranspact') {
                $transpactNumber = $response->CreateTranspactResult;

                if ($transpactNumber < 0) {
                    return $this->returnError("Username not registered with Transpact", 404);
                }

                if ($projectId) {
                    Transpact::where('project_id', $projectId)->update([
                        'transpactnumber' => $transpactNumber,
                    ]);

                    if ($transpactNumber > 0) {
                        Project::where('id', $projectId)->update([
                            'projectstatus'   => '2',
                            'status'          => '1',
                            'admincommission' => $params['OriginatorFixedCommisionOnReceive'],
                        ]);

                        // ProjectTasks::where('project_id', $projectId)->update([
                        //     'taskstatus' => '1',
                        // ]);
                    }
                    $this->transpactCreated($transpactNumber, $projectId, $userId);
                }

                $user              = User::where('id', $userId)->select('email')->first();
                $baseUrl           = config('services.transpact_login_url');
                $appName           = config('app.name');
                $transpactLoginUrl = "{$baseUrl}?Em={$user->email}&co={$appName}";

                return $this->returnSuccess([
                    'response'          => $response,
                    'transpactLoginUrl' =>  $transpactLoginUrl,
                ], 'Transpact Request Created');
            }
            if ($method === 'TranspactHistory') {
                return $this->returnSuccess($response, 'Transpact View Details');
            }
        } catch (SoapFault $e) {
            $errorResponse = json_encode(['error' => $e->getMessage()]);
            $this->storeApiHistory($errorResponse, $userId, $method, $params);
            return $this->handleException($e);
        }
    }
    /**
     * create transpact request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTranspact(Request $request)
    {
        $userId        = $request->attributes->get('decoded_token')->get('id');
        // $projectId     = $request->project_id;
        $projectId     = Crypt::decrypt($request->project_id);
        $project       = Project::find($projectId);
        $user          = User::where('id', $project->contractor_id)->select('email')->first();
        $currencyTypes = config('custom.currency');
        $baseCondition = "Allow the buyer to inspect the goods upon receipt, either at the seller location or upon delivery";
        $escrowData    = EscrowSettings::where('name', 'Transpact')->first();

        Transpact::create([
            'project_id'  => $projectId,
            'customer_id' => $userId,
            'status'      => '0',
        ]);

        $MoneySenderEmail          = $request->attributes->get('decoded_token')->get('email');
        $MoneyRecipientEmail       = $user->email;
        $createType                = (int) env('TRANSPACT_CREATE_TYPE');
        $maxDaysDisputePayWait     = (int) env('MAXDAYS_DISPUTEPAY') ?? 14;
        $Currency                  = $currencyTypes[$project->currency] ?? 'GBP';
        $Amount                    = $project->projectamount ?? 0;
        $projectAmount             = $this->calculateFee('GBP', $Amount);
        $Conditions                = $project->conditions ?? $baseCondition;
        $IsTest                    = env('IS_TEST');
        $ConditionsConsumerClause  = env('CONDITIONS_CLAUSE');
        $TranspactNominatedReferee = env('TRANSPACT_REFEREE');
        $NameReferee               = env('NAME_REFEREE') ?? 'Construction Payment Scheme';
        $EmailReferee              = env('ARBITRATOR_EMAIL');
        $NatureOfTransaction       = $project->project_type;
        $deductionFees             = json_decode($escrowData->deductionfee, true)['fee'];
        // $TranspactNominatedReferee = true;

        if ($Amount < 25000) {
            $originatorFixedCommission = $deductionFees['<25000'];
        } elseif ($Amount > 100000) {
            $originatorFixedCommission = $deductionFees['>100000'];
        } else {
            $originatorFixedCommission = $deductionFees['25000-100000'];
        }

        if ($projectAmount['status']) {
            $projectFee     = $projectAmount['fee'];
            $senderShare    = 0.0;
            $recipientShare = 0.0;
            $OriginatorFee  = $senderShare + $recipientShare + $projectFee;
        } else {
            return [
                'status'     => false,
                'message'    => 'An error occurred in Project fee calculation',
                'statusCode' => 500,
            ];
        }
        $errors = [];

        if ($IsTest === true && $Amount >= 10000) {
            $errors['Amount'][] = 'Amount must be less than 10000 in test mode';
        }

        if ($createType === 3) {
            if ($MoneySenderEmail === $MoneyRecipientEmail) {
                $errors['email'][] = 'MoneySenderEmail and MoneyRecipientEmail must not be the same';
            }

            if ($MoneySenderEmail === $escrowData->username) {
                $errors['email'][] = 'MoneySenderEmail and Username must not be the same';
            }

            if ($MoneyRecipientEmail === $escrowData->username) {
                $errors['email'][] = 'MoneyRecipientEmail and Username must not be the same';
            }
        }

        if ($ConditionsConsumerClause === true && strlen($Conditions) > 4000) {
            $errors['Conditions'][] = 'Conditions text exceeds 4000 character limit';
        }

        if (!empty($errors)) {
            return [
                'status'     => false,
                'message'    => 'Validation failed',
                'errors'     => $errors,
                'statusCode' => 422,
            ];
        }
        $params = [
            'Username'                  => $escrowData->username,
            'Password'                  => $escrowData->password,
            'CreateType'                => $createType,
            'MoneySenderEmail'          => $MoneySenderEmail,
            'MoneyRecipientEmail'       => $MoneyRecipientEmail,
            'Amount'                    => (int)$Amount,
            'Currency'                  => $Currency,
            'NatureOfTransaction'       => $NatureOfTransaction,
            'MaxDaysDisputePayWait'     => $maxDaysDisputePayWait,
            'Conditions'                => $Conditions,
            'ConditionsConsumerClause'  => $ConditionsConsumerClause,
            'OriginatorFee'             => $OriginatorFee,
            'RecipientFee'              => $recipientShare,
            'SenderFee'                 => $senderShare,
            'TranspactNominatedReferee' => $TranspactNominatedReferee,
            'IsTest'                    => $IsTest,
            'NameReferee'               => $NameReferee,
            'EmailReferee'              => $EmailReferee,
            'OriginatorFixedCommisionOnReceive' => $originatorFixedCommission,
        ];
        // dd($params);
        return $this->callSoap('CreateTranspact', $params, $projectId, $userId);
    }
    /**
     * Fee calculation function.
     */
    protected function calculateFee($currency, $amount)
    {
        $feeTable = [
            'GBP' => [
                ['max' => 9999.99,        'fee' => 5.98],
                ['max' => 19999.99,       'fee' => 17.98],
                ['max' => 29999.99,       'fee' => 24.98],
                ['max' => 99999.99,       'fee' => 92.32],
                ['max' => 999999999.99,   'fee' => 592.32],
            ],
            'EUR' => [
                ['max' => 14999.99,       'fee' => 6.98],
                ['max' => 29999.99,       'fee' => 21.98],
                ['max' => 49999.99,       'fee' => 44.98],
                ['max' => 109999.99,      'fee' => 104.35],
                ['max' => 999999999.99,   'fee' => 704.35],
            ],
            'USD' => [
                ['max' => 999.99,         'fee' => 34.98],
                ['max' => 19999.99,       'fee' => 49.70],
                ['max' => 49999.99,       'fee' => 79.14],
                ['max' => 149999.99,      'fee' => 132.41],
                ['max' => 999999999.99,   'fee' => 1132.41],
            ],
        ];

        $currency = strtoupper($currency);

        if (!isset($feeTable[$currency])) {
            return [
                'status' => false,
                'message' => "Unsupported currency: $currency",
            ];
        }

        foreach ($feeTable[$currency] as $range) {
            if ($amount <= $range['max']) {
                return [
                    'status' => true,
                    'fee'    => $range['fee'],
                ];
            }
        }

        return [
            'status' => false,
            'message' => "Amount exceeds maximum supported limit",
        ];
    }
    /**
     * void particular transpact request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeApiHistory($response, $userId, $method, $params)
    {
        ApiHistory::create([
            'customer_id'      => $userId ?? null,
            'apiurl'           => env('TRANSPACT_URL'),
            'apimethod'        => $method,
            'request_payload'  => json_encode($params),
            'response_payload' => json_encode($response),
            'apimode'          => env('APP_ENV'),
        ]);
    }
    public function transpactCreated($transactionId, $projectId, $userId)
    {
        $project = Project::where('id', $projectId)->select('customer_id', 'contractor_id', 'projectname', 'projectstatus', 'customer_email')->first();
        $user    = User::where('id', $userId)->select('name')->first();

        $contractorEmail = User::where('id', $project->contractor_id)->select('email')->first();
        ProjectStatusJob::dispatch($contractorEmail->email, $projectId, 'TranspactCreated', null, null);

        $customMessage = "Transaction of {$transactionId} created by {$user->name} for the {$project->projectname} Project";
        $this->projectService->updateNotification($projectId, $customMessage, 'customer');

        $projectStatusList = config('custom.projectstatus');
        $projectStatus     = $projectStatusList[$project->projectstatus];

        $channel = 'invitechannel';
        $channelData = [
            'message'        => $customMessage,
            'projectStatus'  => $projectStatus,
            'projectId'      => $projectId,
            'contractorId'   => $project->contractor_id,
            'customerId'     => $userId,
        ];
        // if (isset($channelData)) {
        //     broadcast(new \App\Events\InviteChannelBroadcast($channelData, 'invitechannel'));
        // }
        $this->sendCpsNotification($channel, [
            'message'        => $customMessage,
            'projectStatus'  => $projectStatus,
            'projectId'      => $projectId,
            'contractorId'   => $project->contractor_id,
            'customerId'     => $userId,
        ], 0, 'broadcast');
    }
    /**
     * view particular transpact request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewTranspactHistory(Request $request)
    {
        $userId          = $request->attributes->get('decoded_token')->get('id');
        try {
            $projectId = Crypt::decrypt($request->project_id);
        } catch (DecryptException $e) {
            $projectId = $request->project_id;
        }
        $transpact       = Transpact::where('project_id', $projectId)->first();
        $transpactNumber = $transpact->transpactnumber;
        $escrowData      = EscrowSettings::where('name', 'Transpact')->first();

        $params = [
            'Username'        => $escrowData->username,
            'Password'        => $escrowData->password,
            'TranspactNumber' => (int)$transpactNumber,
            'IsTest'          => env('IS_TEST'),
        ];

        return $this->callSoap('TranspactHistory', $params, $projectId, $userId);
    }
    public function viewAdminTranspactHistory(Request $request)
    {
        $userId          = $request->attributes->get('decoded_token')->get('id');
        $transpactNumber = $request->transpact_number;

        $escrowData = EscrowSettings::where('name', 'Transpact')->first();

        $params = [
            'Username'        => $escrowData->username,
            'Password'        => $escrowData->password,
            'TranspactNumber' => (int)$transpactNumber,
            'IsTest'          => env('IS_TEST'),
        ];

        return $this->callSoap('TranspactHistory', $params, null, $userId);
    }
}
