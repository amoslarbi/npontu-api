<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Namshi\JOSE\SimpleJWS;

if (!function_exists('checkJWT')) {
    function checkJWT($bearer)
    {
        $secret = config('jwt.secret');
        $jws = SimpleJWS::load($bearer);
        if (!$jws->isValid($secret)) {
            return response()->json([
                'success' => false,
                'message' => 'A token is required',
            ], 401); // unauthorized
        }
        return auth()->user();
    }
}

if (!function_exists('queryChecker')) {
    function queryChecker($query, $queryOwer)
    {
        try {
            $query;
        } catch (\Illuminate\Database\QueryException $error) {
            // telegram bot goes here
            // telegramCriticalFailuresBot(urlencode("Hi! Admin\nEndpoint execution failed, find details:\nEndpoint: " . $queryOwer . "\nError: " . $error . "\nTimestamp: " . date("F j, Y, g:i a")), "-507050651");
            return response()->json([
                'success' => false,
                'error' => $error,
                'message' => 'Could not connect to server',
            ]);
        }
    }
}

if (!function_exists('createNewToken')) {
    function createNewToken($token, $theData)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTFactory::getTTL() * 60,
            'data' => $theData
        ], 200);
    }
}
