<?php

if (!function_exists('generateJWT')) {
    function generateJWT($payload)
    {
        $key = getenv('JWT_SECRET') ?: 'your-secret-key-change-this-in-production';
        return Firebase\JWT\JWT::encode($payload, $key, 'HS256');
    }
}

if (!function_exists('verifyJWT')) {
    function verifyJWT($token)
    {
        try {
            $key = getenv('JWT_SECRET') ?: 'your-secret-key-change-this-in-production';
            return Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key($key, 'HS256'));
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId($request)
    {
        $authHeader = $request->getHeader('Authorization');
        
        if (!$authHeader) {
            return null;
        }

        $token = str_replace('Bearer ', '', $authHeader->getValue());
        $decoded = verifyJWT($token);
        
        return $decoded ? $decoded->user_id : null;
    }
}
