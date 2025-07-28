<?php

namespace App\Traits;

use Exception;
use GuzzleHttp\Client;

trait sendCpsNotification
{
    public function sendCpsNotification($channel, $message, $state, $type = 'broadcast')
    {
        try {
            $endPort           = env('SOCKET_PORT');
            $endUrl            = env('SOCKET_URL');
            $endBroadcast      = env('SOCKET_BRODCASTURL');
            $socketEnvironment = env('SOCKET_ENVIRONMENT');

            $baseUrl  = $endUrl . ($socketEnvironment == 'localhost' ? ":$endPort" : "");
            $endpoint = $baseUrl . ($type === 'message' ? '/message' : "/$endBroadcast");

            if (is_array($message)) {
                $message = json_encode($message);
            }
            $queryParams = http_build_query([
                'channel'        => $channel,
                'state'          => 0,
                'customMessage'  => $message
            ]);
            $url      = "$endpoint?$queryParams";
            $headers = [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json'
            ];

            $client   = new Client();
            $response = $client->request('GET', $url, ['headers' => $headers]);
            return [
                'status'   => true,
                'response' => json_decode($response->getBody(), true),
            ];
        } catch (Exception $e) {
            return ['statusCode' => $e->getCode(), 'message' => $e->getMessage(), 'body' => null];
        }
    }
}
