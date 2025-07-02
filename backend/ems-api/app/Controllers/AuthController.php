<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends ResourceController
{
    use ResponseTrait;

    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        helper('jwt');
    }

    /**
     * Login user
     */
    public function login()
    {
        // Get JSON input for API requests first
        $input = $this->request->getJSON(true);
        if (!$input) {
            // Fallback to POST data
            $input = [
                'username' => $this->request->getPost('username'),
                'password' => $this->request->getPost('password')
            ];
        }

        $rules = [
            'username' => 'required',
            'password' => 'required'
        ];

        if (!$this->validate($rules, $input)) {
            return $this->fail($this->validator->getErrors());
        }

        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($username) || empty($password)) {
            return $this->failUnauthorized('Username and password are required');
        }

        // Check if user exists and verify password
        $user = $this->userModel->verifyPassword($username, $password);

        if (!$user) {
            return $this->failUnauthorized('Invalid credentials');
        }

        // Check if user is active
        if ($user['status'] !== 'active') {
            return $this->failUnauthorized('Account is inactive');
        }

        // Update last login
        $this->userModel->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);

        // Get user roles and permissions
        $userWithRoles = $this->userModel->getUserWithRolesAndPermissions($user['id']);

        // Generate JWT token
        $key = getenv('JWT_SECRET') ?: 'your-secret-key';
        $payload = [
            'iss' => 'ems-api',
            'aud' => 'ems-frontend',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24 hours
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ];

        $token = JWT::encode($payload, $key, 'HS256');

        return $this->respond([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'avatar' => $user['avatar'],
                    'status' => $user['status'],
                    'roles' => $userWithRoles['roles'] ?? [],
                    'permissions' => $userWithRoles['permissions'] ?? []
                ]
            ]
        ]);
    }

    /**
     * Register new user
     */
    public function register()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'full_name' => 'required|max_length[100]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'username' => $this->request->getPost('username'),
            'email' => $this->request->getPost('email'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'full_name' => $this->request->getPost('full_name'),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            $userId = $this->userModel->insert($data);
            
            if ($userId) {
                return $this->respondCreated([
                    'status' => 'success',
                    'message' => 'User registered successfully',
                    'data' => ['user_id' => $userId]
                ]);
            } else {
                return $this->fail('Failed to register user');
            }
        } catch (\Exception $e) {
            return $this->fail('Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        // For JWT, logout is handled on frontend by removing token
        return $this->respond([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get current user profile
     */
    public function profile()
    {
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            return $this->failUnauthorized('Access denied');
        }

        $user = $this->userModel->getUserWithRolesAndPermissions($userId);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        return $this->respond([
            'status' => 'success',
            'data' => $user
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile()
    {
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            return $this->failUnauthorized('Access denied');
        }

        $rules = [
            'full_name' => 'required|max_length[100]',
            'email' => "required|valid_email|is_unique[users.email,id,{$userId}]"
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'full_name' => $this->request->getPost('full_name'),
            'email' => $this->request->getPost('email')
        ];

        if ($this->request->getPost('password')) {
            $data['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
        }

        try {
            $this->userModel->update($userId, $data);
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Profile updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to update profile: ' . $e->getMessage());
        }
    }

    /**
     * Debug endpoint to check user data
     */
    public function debug()
    {
        $users = $this->userModel->select('id, username, email, full_name, status, created_at')->findAll();
        
        return $this->respond([
            'status' => 'success',
            'message' => 'User debug data',
            'users' => $users,
            'test_password_hash' => password_hash('admin123', PASSWORD_DEFAULT)
        ]);
    }

    /**
     * Get current user ID from JWT token
     */
    private function getCurrentUserId()
    {
        $authHeader = $this->request->getHeader('Authorization');
        
        if (!$authHeader) {
            return null;
        }

        $token = str_replace('Bearer ', '', $authHeader->getValue());
        
        try {
            $key = getenv('JWT_SECRET') ?: 'your-secret-key';
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return $decoded->user_id;
        } catch (\Exception $e) {
            return null;
        }
    }
}
