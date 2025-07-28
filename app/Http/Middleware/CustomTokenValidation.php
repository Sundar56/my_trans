<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Illuminate\Support\Facades\Crypt;
use App\Http\Controllers\Api\BaseController;

class CustomTokenValidation extends BaseController
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authorizationHeader = $request->header('Authorization');
        if ($authorizationHeader && preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            $token = $matches[1]; // Extract the token

            try {
                $decoded             = JWTAuth::setToken($token)->getPayload();
                $encryptedClaimsBlob = $decoded->get('encryptedData');
                try {
                    $decryptedJson = Crypt::decryptString($encryptedClaimsBlob);
                    $claimsArray   = json_decode($decryptedJson, true);
                } catch (\Exception $e) {
                    return $this->sendError('Unauthorised.', ['error' => ['Token is invalid or expired.']], 401);
                }

                if ($decoded->get('exp') < time()) {
                    return $this->sendError('Unauthorised.', ['error' => ['Token has expired']], 401);
                }

                // Validating user
                $userId   = $claimsArray['id'];
                $userData = \App\Models\User::find($userId);
                if (!$userData) {
                    return $this->sendError('Unauthorised.', ['error' => ['User not found']], 401);
                }

                // Attach the decoded token to the request if needed
                $request->attributes->add(['decoded_token' => collect($claimsArray)]);
            } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
                return $this->sendError('Unauthorised.', ['error' => ['Token has expired']], 401);
            } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
                return $this->sendError('Unauthorised.', ['error' => ['Token has invalid']], 401);
            } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
                return $this->sendError('Unauthorised.', ['error' => ['Token could not be parsed']], 401);
            } catch (Exception $e) {
                return $this->sendError('Unauthorised.', ['error' => ['An error occurred while processing the token']], 401);
            }
        } else {
            return $this->sendError('Unauthorised.', ['error' => ['Authorization header not found']], 401);
        }
        return $next($request);
    }
}
