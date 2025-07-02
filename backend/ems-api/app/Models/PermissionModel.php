<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['name', 'group_name'];

    // Dates
    protected $useTimestamps = false;

    // Validation
    protected $validationRules = [
        'name' => 'required|max_length[100]|is_unique[permissions.name,id,{id}]',
        'group_name' => 'required|max_length[50]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Permission name is required',
            'max_length' => 'Permission name cannot exceed 100 characters',
            'is_unique' => 'Permission name already exists'
        ],
        'group_name' => [
            'required' => 'Group name is required',
            'max_length' => 'Group name cannot exceed 50 characters'
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
     * Get permissions grouped by group_name
     */
    public function getPermissionsGrouped()
    {
        $permissions = $this->orderBy('group_name', 'ASC')
                           ->orderBy('name', 'ASC')
                           ->findAll();
        
        $grouped = [];
        foreach ($permissions as $permission) {
            $grouped[$permission['group_name']][] = $permission;
        }
        
        return $grouped;
    }

    /**
     * Get permissions by group name
     */
    public function getPermissionsByGroup($groupName)
    {
        return $this->where('group_name', $groupName)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }

    /**
     * Get all unique group names
     */
    public function getGroups()
    {
        return $this->select('group_name')
                   ->distinct()
                   ->orderBy('group_name', 'ASC')
                   ->findColumn('group_name');
    }

    /**
     * Get permissions by role ID
     */
    public function getPermissionsByRole($roleId)
    {
        return $this->select('permissions.*')
                   ->join('role_permissions', 'role_permissions.permission_id = permissions.id')
                   ->where('role_permissions.role_id', $roleId)
                   ->findAll();
    }

    /**
     * Check if a permission is assigned to a role
     */
    public function isAssignedToRole($permissionId, $roleId)
    {
        $db = \Config\Database::connect();
        $count = $db->table('role_permissions')
                   ->where('permission_id', $permissionId)
                   ->where('role_id', $roleId)
                   ->countAllResults();
        
        return $count > 0;
    }
}
