<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('jwt');
        
        $authHeader = $request->getHeader('Authorization');
        
        if (!$authHeader) {
            return service('response')
                ->setJSON(['status' => 'error', 'message' => 'Authorization header missing'])
                ->setStatusCode(401);
        }

        $token = str_replace('Bearer ', '', $authHeader->getValue());
        
        if (!$token) {
            return service('response')
                ->setJSON(['status' => 'error', 'message' => 'Token missing'])
                ->setStatusCode(401);
        }

        $decoded = verifyJWT($token);
        
        if (!$decoded) {
            return service('response')
                ->setJSON(['status' => 'error', 'message' => 'Invalid or expired token'])
                ->setStatusCode(401);
        }

        // Add user info to request for use in controllers
        $request->user_id = $decoded->user_id;
        $request->username = $decoded->username;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do after
    }
}
