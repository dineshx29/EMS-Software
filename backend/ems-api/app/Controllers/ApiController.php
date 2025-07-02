<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class ApiController extends ResourceController
{
    use ResponseTrait;

    /**
     * API Health Check
     */
    public function index()
    {
        return $this->respond([
            'status' => 'success',
            'message' => 'EMS API is running successfully!',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'auth' => [
                    'POST /api/auth/login',
                    'POST /api/auth/register',
                    'GET /api/auth/profile',
                    'PUT /api/auth/profile'
                ],
                'dashboard' => [
                    'GET /api/dashboard',
                    'GET /api/dashboard/user',
                    'GET /api/dashboard/analytics'
                ],
                'employees' => [
                    'GET /api/employees',
                    'POST /api/employees',
                    'GET /api/employees/{id}',
                    'PUT /api/employees/{id}',
                    'DELETE /api/employees/{id}'
                ],
                'departments' => [
                    'GET /api/departments',
                    'POST /api/departments',
                    'GET /api/departments/{id}',
                    'PUT /api/departments/{id}',
                    'DELETE /api/departments/{id}'
                ],
                'users' => [
                    'GET /api/users',
                    'POST /api/users',
                    'GET /api/users/{id}',
                    'PUT /api/users/{id}',
                    'DELETE /api/users/{id}'
                ]
            ]
        ]);
    }

    /**
     * Database connectivity test
     */
    public function dbTest()
    {
        try {
            $db = \Config\Database::connect();
            $query = $db->query('SELECT COUNT(*) as count FROM users');
            $result = $query->getRow();
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Database connection successful',
                'user_count' => $result->count,
                'database' => 'ems_datas'
            ]);
        } catch (\Exception $e) {
            return $this->fail([
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ]);
        }
    }
}
