<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RoleModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class UserController extends ResourceController
{
    use ResponseTrait;

    protected $userModel;
    protected $roleModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        helper('jwt');
    }

    /**
     * Return an array of resource objects, themselves in array format
     */
    public function index()
    {
        try {
            $users = $this->userModel->getUsersWithRoles();
            
            return $this->respond([
                'status' => 'success',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch users: ' . $e->getMessage());
        }
    }

    /**
     * Return the properties of a resource object
     */
    public function show($id = null)
    {
        try {
            $user = $this->userModel->getUserWithRolesAndPermissions($id);
            
            if (!$user) {
                return $this->failNotFound('User not found');
            }

            // Remove password from response
            unset($user['password']);

            return $this->respond([
                'status' => 'success',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch user: ' . $e->getMessage());
        }
    }

    /**
     * Create a new resource object, from "posted" parameters
     */
    public function create()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'full_name' => 'required|max_length[100]',
            'status' => 'permit_empty|in_list[active,inactive]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'username' => $this->request->getPost('username'),
            'email' => $this->request->getPost('email'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'full_name' => $this->request->getPost('full_name'),
            'avatar' => $this->request->getPost('avatar'),
            'status' => $this->request->getPost('status') ?: 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            $userId = $this->userModel->insert($data);
            
            if ($userId) {
                // Assign roles if provided
                $roles = $this->request->getPost('roles');
                if ($roles && is_array($roles)) {
                    $this->userModel->assignRoles($userId, $roles);
                }

                $user = $this->userModel->getUserWithRolesAndPermissions($userId);
                unset($user['password']);
                
                return $this->respondCreated([
                    'status' => 'success',
                    'message' => 'User created successfully',
                    'data' => $user
                ]);
            } else {
                return $this->fail('Failed to create user');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Add or update a model resource, from "posted" properties
     */
    public function update($id = null)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        $rules = [
            'username' => "required|min_length[3]|max_length[50]|is_unique[users.username,id,{$id}]",
            'email' => "required|valid_email|is_unique[users.email,id,{$id}]",
            'full_name' => 'required|max_length[100]',
            'status' => 'permit_empty|in_list[active,inactive]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $inputData = $this->request->getRawInput() ?: $this->request->getPost();
        
        $data = [
            'username' => $inputData['username'],
            'email' => $inputData['email'],
            'full_name' => $inputData['full_name'],
            'avatar' => $inputData['avatar'] ?? null,
            'status' => $inputData['status'] ?? 'active'
        ];

        // Only update password if provided
        if (!empty($inputData['password'])) {
            $data['password'] = password_hash($inputData['password'], PASSWORD_DEFAULT);
        }

        try {
            $this->userModel->update($id, $data);

            // Update roles if provided
            if (isset($inputData['roles']) && is_array($inputData['roles'])) {
                $this->userModel->assignRoles($id, $inputData['roles']);
            }
            
            $updatedUser = $this->userModel->getUserWithRolesAndPermissions($id);
            unset($updatedUser['password']);
            
            return $this->respond([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => $updatedUser
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Delete the designated resource object from the model
     */
    public function delete($id = null)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        try {
            $this->userModel->delete($id);
            
            return $this->respondDeleted([
                'status' => 'success',
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Get users with pagination and filtering
     */
    public function paginated()
    {
        $page = $this->request->getGet('page') ?? 1;
        $perPage = $this->request->getGet('per_page') ?? 10;
        $search = $this->request->getGet('search') ?? '';
        $status = $this->request->getGet('status') ?? '';

        try {
            $builder = $this->userModel->select('users.id, users.username, users.email, users.full_name, users.avatar, users.status, users.last_login, users.created_at');
            
            if (!empty($search)) {
                $builder = $builder->groupStart()
                    ->like('users.username', $search)
                    ->orLike('users.email', $search)
                    ->orLike('users.full_name', $search)
                    ->groupEnd();
            }

            if (!empty($status)) {
                $builder = $builder->where('users.status', $status);
            }

            $users = $builder->paginate($perPage, 'default', $page);
            $pager = $this->userModel->pager;

            return $this->respond([
                'status' => 'success',
                'data' => $users,
                'pagination' => [
                    'current_page' => $pager->getCurrentPage(),
                    'per_page' => $pager->getPerPage(),
                    'total' => $pager->getTotal(),
                    'total_pages' => $pager->getPageCount()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch users: ' . $e->getMessage());
        }
    }

    /**
     * Assign roles to user
     */
    public function assignRoles($id = null)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        $rules = [
            'roles' => 'required|is_array',
            'roles.*' => 'required|integer|is_not_unique[roles.id]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $inputData = $this->request->getRawInput() ?: $this->request->getPost();
        $roles = $inputData['roles'];

        try {
            $this->userModel->assignRoles($id, $roles);
            
            $user = $this->userModel->getUserWithRolesAndPermissions($id);
            unset($user['password']);
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Roles assigned successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to assign roles: ' . $e->getMessage());
        }
    }

    /**
     * Change user password
     */
    public function changePassword($id = null)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        $rules = [
            'current_password' => 'required',
            'new_password' => 'required|min_length[6]',
            'confirm_password' => 'required|matches[new_password]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $inputData = $this->request->getRawInput() ?: $this->request->getPost();

        // Verify current password
        if (!password_verify($inputData['current_password'], $user['password'])) {
            return $this->fail('Current password is incorrect');
        }

        try {
            $this->userModel->update($id, [
                'password' => password_hash($inputData['new_password'], PASSWORD_DEFAULT)
            ]);
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Password changed successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to change password: ' . $e->getMessage());
        }
    }
}
