<?php

namespace App\Api\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Api\Common\Models\WebhookHistory;
use App\Services\ProjectService;
use App\Models\User;
use App\Models\ModelHasRoles;
use App\Api\Frontend\Modules\Project\Models\Project;
use App\Api\Frontend\Modules\Transpact\Models\Transpact;

class WebhookController extends Controller
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }
    /**
     * Callback Transpact function.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function callbackTranspact(Request $request)
    {
        $statusCode      = 1;
        $message         = "Callback transpact";
        $currentMethod   = strtolower($request->method());
        $payload         = @file_get_contents('php://input');
        $payloadresponse = json_decode($payload, true);

        Log::channel('webhooks')->info("Enter Transpact webhooks \n");
        Log::channel('webhooks')->info("Transpact method:: $currentMethod \n");
        Log::channel('webhooks')->info("Transpact payload:: $payload \n");

        parse_str($payload, $data);

        $this->projectService->updateProjectStatus($data);

        if (!empty($data['description'])) {
            $user     = User::where('email', $data['description'])->select('id')->first();
            $userRole = ModelHasRoles::where('model_id', $user->id)->select('role_id')->first();

            if ($userRole) {
                if ($userRole->role_id == 2) {
                    $customerEmail = $data['description'];
                } elseif ($userRole->role_id == 3) {
                    $contractorEmail = $data['description'];
                }
            }
        } else {
            return $statusCode;
        }

        WebhookHistory::create([
            'customer_email'   => $customerEmail ?? null,
            'contractor_email' => $contractorEmail ?? null,
            'transactionid'    => $data['transactionID'] ?? null,
            'eventid'          => $data['transactionEventID'] ?? null,
            'amount'           => $data['amount'] ?? null,
            'istest'           => $data['IsTest'] ?? null,
            'payload'          => $payload ? json_encode($payload) : json_encode([]),
        ]);

        return $statusCode;
    }
}
