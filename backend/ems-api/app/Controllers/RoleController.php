<?php

namespace App\Controllers;

use App\Models\RoleModel;
use App\Models\PermissionModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class RoleController extends ResourceController
{
    use ResponseTrait;

    protected $roleModel;
    protected $permissionModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->permissionModel = new PermissionModel();
        helper('jwt');
    }

    /**
     * Return an array of resource objects, themselves in array format
     */
    public function index()
    {
        try {
            $roles = $this->roleModel->getRolesWithPermissions();
            
            return $this->respond([
                'status' => 'success',
                'data' => $roles
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch roles: ' . $e->getMessage());
        }
    }

    /**
     * Return the properties of a resource object
     */
    public function show($id = null)
    {
        try {
            $role = $this->roleModel->getRoleWithPermissions($id);
            
            if (!$role) {
                return $this->failNotFound('Role not found');
            }

            return $this->respond([
                'status' => 'success',
                'data' => $role
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch role: ' . $e->getMessage());
        }
    }

    /**
     * Create a new resource object, from "posted" parameters
     */
    public function create()
    {
        $rules = [
            'name' => 'required|min_length[2]|max_length[50]|is_unique[roles.name]',
            'description' => 'permit_empty|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            $roleId = $this->roleModel->insert($data);
            
            if ($roleId) {
                // Assign permissions if provided
                $permissions = $this->request->getPost('permissions');
                if ($permissions && is_array($permissions)) {
                    $this->roleModel->assignPermissions($roleId, $permissions);
                }

                $role = $this->roleModel->getRoleWithPermissions($roleId);
                
                return $this->respondCreated([
                    'status' => 'success',
                    'message' => 'Role created successfully',
                    'data' => $role
                ]);
            } else {
                return $this->fail('Failed to create role');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * Add or update a model resource, from "posted" properties
     */
    public function update($id = null)
    {
        $role = $this->roleModel->find($id);
        
        if (!$role) {
            return $this->failNotFound('Role not found');
        }

        $rules = [
            'name' => "required|min_length[2]|max_length[50]|is_unique[roles.name,id,{$id}]",
            'description' => 'permit_empty|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $inputData = $this->request->getRawInput() ?: $this->request->getPost();
        
        $data = [
            'name' => $inputData['name'],
            'description' => $inputData['description'] ?? null
        ];

        try {
            $this->roleModel->update($id, $data);

            // Update permissions if provided
            if (isset($inputData['permissions']) && is_array($inputData['permissions'])) {
                $this->roleModel->assignPermissions($id, $inputData['permissions']);
            }
            
            $updatedRole = $this->roleModel->getRoleWithPermissions($id);
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Role updated successfully',
                'data' => $updatedRole
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to update role: ' . $e->getMessage());
        }
    }

    /**
     * Delete the designated resource object from the model
     */
    public function delete($id = null)
    {
        $role = $this->roleModel->find($id);
        
        if (!$role) {
            return $this->failNotFound('Role not found');
        }

        try {
            // Check if role is assigned to users
            $userModel = new \App\Models\UserModel();
            $userCount = $userModel->join('user_roles', 'user_roles.user_id = users.id')
                                   ->where('user_roles.role_id', $id)
                                   ->countAllResults();
            
            if ($userCount > 0) {
                return $this->fail('Cannot delete role that is assigned to users');
            }

            $this->roleModel->delete($id);
            
            return $this->respondDeleted([
                'status' => 'success',
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to delete role: ' . $e->getMessage());
        }
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissions($id = null)
    {
        $role = $this->roleModel->find($id);
        
        if (!$role) {
            return $this->failNotFound('Role not found');
        }

        $rules = [
            'permissions' => 'required|is_array',
            'permissions.*' => 'required|integer|is_not_unique[permissions.id]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $inputData = $this->request->getRawInput() ?: $this->request->getPost();
        $permissions = $inputData['permissions'];

        try {
            $this->roleModel->assignPermissions($id, $permissions);
            
            $role = $this->roleModel->getRoleWithPermissions($id);
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Permissions assigned successfully',
                'data' => $role
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to assign permissions: ' . $e->getMessage());
        }
    }

    /**
     * Get roles with pagination
     */
    public function paginated()
    {
        $page = $this->request->getGet('page') ?? 1;
        $perPage = $this->request->getGet('per_page') ?? 10;
        $search = $this->request->getGet('search') ?? '';

        try {
            $builder = $this->roleModel;
            
            if (!empty($search)) {
                $builder = $builder->groupStart()
                    ->like('name', $search)
                    ->orLike('description', $search)
                    ->groupEnd();
            }

            $roles = $builder->paginate($perPage, 'default', $page);
            $pager = $this->roleModel->pager;

            return $this->respond([
                'status' => 'success',
                'data' => $roles,
                'pagination' => [
                    'current_page' => $pager->getCurrentPage(),
                    'per_page' => $pager->getPerPage(),
                    'total' => $pager->getTotal(),
                    'total_pages' => $pager->getPageCount()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch roles: ' . $e->getMessage());
        }
    }
}
