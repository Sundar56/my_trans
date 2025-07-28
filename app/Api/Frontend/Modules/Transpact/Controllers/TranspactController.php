<?php

namespace App\Api\Frontend\Modules\Transpact\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use SoapClient;
use SoapHeader;
use SoapFault;
use Artisaninweb\SoapWrapper\SoapWrapper;
use App\Services\TranspactService;


class TranspactController extends BaseController
{
    protected $transpactService;

    public function __construct(TranspactService $transpactService)
    {
        $this->transpactService   = $transpactService;
    }
     /**
     * create transpact request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTranspact(Request $request)
    {
        $response = $this->transpactService->createTranspact($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
     /**
     * view particular transpact request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewTranspactHistory(Request $request)
    {
        $response = $this->transpactService->viewTranspactHistory($request);

        if (!$response['status']) {
            return $this->sendError($response['message'], $response['errors'] ?? [], $response['statusCode']);
        }
        return $this->sendResponse($response['data'], $response['message']);
    }
}
