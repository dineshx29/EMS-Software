<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['name', 'description', 'created_at'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $deletedField = '';

    // Validation
    protected $validationRules = [
        'name' => 'required|max_length[50]|is_unique[roles.name,id,{id}]',
        'description' => 'max_length[255]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Role name is required',
            'max_length' => 'Role name cannot exceed 50 characters',
            'is_unique' => 'Role name already exists'
        ],
        'description' => [
            'max_length' => 'Description cannot exceed 255 characters'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Get role with permissions
     */
    public function getRoleWithPermissions($roleId)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('roles r');
        
        return $builder->select('r.*, GROUP_CONCAT(p.name) as permissions, GROUP_CONCAT(p.id) as permission_ids')
                      ->join('role_permissions rp', 'rp.role_id = r.id', 'left')
                      ->join('permissions p', 'p.id = rp.permission_id', 'left')
                      ->where('r.id', $roleId)
                      ->groupBy('r.id')
                      ->get()
                      ->getRowArray();
    }

    /**
     * Get all roles with permissions
     */
    public function getRolesWithPermissions()
    {
        return $this->select('roles.*, GROUP_CONCAT(permissions.name SEPARATOR ", ") as permission_names')
                   ->join('role_permissions', 'role_permissions.role_id = roles.id', 'left')
                   ->join('permissions', 'permissions.id = role_permissions.permission_id', 'left')
                   ->groupBy('roles.id')
                   ->findAll();
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissions($roleId, $permissionIds)
    {
        $db = \Config\Database::connect();
        
        // First delete existing permissions
        $db->table('role_permissions')->where('role_id', $roleId)->delete();
        
        // Then insert new permissions
        if (!empty($permissionIds)) {
            $data = [];
            foreach ($permissionIds as $permissionId) {
                $data[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId
                ];
            }
            $db->table('role_permissions')->insertBatch($data);
        }
        
        return true;
    }

    public function removePermission($roleId, $permissionId)
    {
        $db = \Config\Database::connect();
        return $db->table('role_permissions')
                 ->where('role_id', $roleId)
                 ->where('permission_id', $permissionId)
                 ->delete();
    }

    public function hasPermission($roleId, $permissionName)
    {
        $role = $this->getRoleWithPermissions($roleId);
        
        if (!$role || empty($role['permissions'])) {
            return false;
        }
        
        foreach ($role['permissions'] as $permission) {
            if ($permission['name'] === $permissionName) {
                return true;
            }
        }
        
        return false;
    }

    public function getRolesByPermission($permissionId)
    {
        return $this->select('roles.*')
                   ->join('role_permissions', 'role_permissions.role_id = roles.id')
                   ->where('role_permissions.permission_id', $permissionId)
                   ->findAll();
    }
}
