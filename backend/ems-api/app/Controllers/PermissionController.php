<?php

namespace App\Controllers;

use App\Models\PermissionModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class PermissionController extends ResourceController
{
    use ResponseTrait;

    protected $permissionModel;

    public function __construct()
    {
        $this->permissionModel = new PermissionModel();
        helper('jwt');
    }

    /**
     * Return an array of resource objects, themselves in array format
     */
    public function index()
    {
        try {
            $permissions = $this->permissionModel->getPermissionsGrouped();
            
            return $this->respond([
                'status' => 'success',
                'data' => $permissions
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch permissions: ' . $e->getMessage());
        }
    }

    /**
     * Return all permissions as flat list
     */
    public function all()
    {
        try {
            $permissions = $this->permissionModel->findAll();
            
            return $this->respond([
                'status' => 'success',
                'data' => $permissions
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch permissions: ' . $e->getMessage());
        }
    }

    /**
     * Return the properties of a resource object
     */
    public function show($id = null)
    {
        try {
            $permission = $this->permissionModel->find($id);
            
            if (!$permission) {
                return $this->failNotFound('Permission not found');
            }

            return $this->respond([
                'status' => 'success',
                'data' => $permission
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch permission: ' . $e->getMessage());
        }
    }

    /**
     * Create a new resource object, from "posted" parameters
     */
    public function create()
    {
        $rules = [
            'name' => 'required|min_length[2]|max_length[100]|is_unique[permissions.name]',
            'group_name' => 'required|min_length[2]|max_length[50]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'group_name' => $this->request->getPost('group_name')
        ];

        try {
            $id = $this->permissionModel->insert($data);
            
            if ($id) {
                $permission = $this->permissionModel->find($id);
                return $this->respondCreated([
                    'status' => 'success',
                    'message' => 'Permission created successfully',
                    'data' => $permission
                ]);
            } else {
                return $this->fail('Failed to create permission');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to create permission: ' . $e->getMessage());
        }
    }

    /**
     * Add or update a model resource, from "posted" properties
     */
    public function update($id = null)
    {
        $permission = $this->permissionModel->find($id);
        
        if (!$permission) {
            return $this->failNotFound('Permission not found');
        }

        $rules = [
            'name' => "required|min_length[2]|max_length[100]|is_unique[permissions.name,id,{$id}]",
            'group_name' => 'required|min_length[2]|max_length[50]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $inputData = $this->request->getRawInput() ?: $this->request->getPost();
        
        $data = [
            'name' => $inputData['name'],
            'group_name' => $inputData['group_name']
        ];

        try {
            $this->permissionModel->update($id, $data);
            
            $updatedPermission = $this->permissionModel->find($id);
            return $this->respond([
                'status' => 'success',
                'message' => 'Permission updated successfully',
                'data' => $updatedPermission
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to update permission: ' . $e->getMessage());
        }
    }

    /**
     * Delete the designated resource object from the model
     */
    public function delete($id = null)
    {
        $permission = $this->permissionModel->find($id);
        
        if (!$permission) {
            return $this->failNotFound('Permission not found');
        }

        try {
            // Check if permission is assigned to roles
            $roleModel = new \App\Models\RoleModel();
            $roleCount = $roleModel->join('role_permissions', 'role_permissions.role_id = roles.id')
                                   ->where('role_permissions.permission_id', $id)
                                   ->countAllResults();
            
            if ($roleCount > 0) {
                return $this->fail('Cannot delete permission that is assigned to roles');
            }

            $this->permissionModel->delete($id);
            
            return $this->respondDeleted([
                'status' => 'success',
                'message' => 'Permission deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to delete permission: ' . $e->getMessage());
        }
    }

    /**
     * Get permissions by group
     */
    public function byGroup($groupName = null)
    {
        try {
            $permissions = $this->permissionModel->where('group_name', $groupName)->findAll();
            
            return $this->respond([
                'status' => 'success',
                'data' => $permissions
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch permissions: ' . $e->getMessage());
        }
    }

    /**
     * Get permission groups
     */
    public function groups()
    {
        try {
            $groups = $this->permissionModel->select('group_name')
                                          ->distinct()
                                          ->findAll();
            
            $groupNames = array_column($groups, 'group_name');
            
            return $this->respond([
                'status' => 'success',
                'data' => $groupNames
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch permission groups: ' . $e->getMessage());
        }
    }

    /**
     * Get permissions with pagination
     */
    public function paginated()
    {
        $page = $this->request->getGet('page') ?? 1;
        $perPage = $this->request->getGet('per_page') ?? 10;
        $search = $this->request->getGet('search') ?? '';
        $group = $this->request->getGet('group') ?? '';

        try {
            $builder = $this->permissionModel;
            
            if (!empty($search)) {
                $builder = $builder->like('name', $search);
            }

            if (!empty($group)) {
                $builder = $builder->where('group_name', $group);
            }

            $permissions = $builder->paginate($perPage, 'default', $page);
            $pager = $this->permissionModel->pager;

            return $this->respond([
                'status' => 'success',
                'data' => $permissions,
                'pagination' => [
                    'current_page' => $pager->getCurrentPage(),
                    'per_page' => $pager->getPerPage(),
                    'total' => $pager->getTotal(),
                    'total_pages' => $pager->getPageCount()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch permissions: ' . $e->getMessage());
        }
    }
}
