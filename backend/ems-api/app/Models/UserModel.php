<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['username', 'password', 'email', 'full_name', 'avatar', 'status', 'last_login', 'created_at'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $deletedField = '';

    // Validation
    protected $validationRules = [
        'username' => 'required|max_length[50]|is_unique[users.username,id,{id}]',
        'password' => 'required|min_length[6]',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'full_name' => 'max_length[100]',
        'avatar' => 'max_length[255]',
        'status' => 'in_list[active,inactive]'
    ];

    protected $validationMessages = [
        'username' => [
            'required' => 'Username is required',
            'max_length' => 'Username cannot exceed 50 characters',
            'is_unique' => 'Username already exists'
        ],
        'password' => [
            'required' => 'Password is required',
            'min_length' => 'Password must be at least 6 characters'
        ],
        'email' => [
            'required' => 'Email is required',
            'valid_email' => 'Please enter a valid email address',
            'is_unique' => 'Email already exists'
        ],
        'status' => [
            'in_list' => 'Status must be either active or inactive'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['hashPassword'];
    protected $afterInsert = [];
    protected $beforeUpdate = ['hashPassword'];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = ['removePassword'];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Hash password before saving
     */
    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    /**
     * Remove password from results
     */
    protected function removePassword(array $data)
    {
        if (isset($data['data'])) {
            if (is_array($data['data'])) {
                // Multiple records
                if (isset($data['data'][0])) {
                    foreach ($data['data'] as &$record) {
                        if (isset($record['password'])) {
                            unset($record['password']);
                        }
                    }
                } else {
                    // Single record
                    if (isset($data['data']['password'])) {
                        unset($data['data']['password']);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Verify password
     */
    public function verifyPassword($username, $password)
    {
        // Use query builder to bypass model callbacks
        $db = \Config\Database::connect();
        $builder = $db->table('users');
        
        $user = $builder->select('id, username, password, email, full_name, avatar, status, last_login, created_at')
                       ->where('username', $username)
                       ->orWhere('email', $username)
                       ->get()
                       ->getRowArray();

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }

        return false;
    }

    /**
     * Update last login
     */
    public function updateLastLogin($userId)
    {
        return $this->update($userId, ['last_login' => date('Y-m-d H:i:s')]);
    }

    /**
     * Get user with roles
     */
    public function getUserWithRoles($userId)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('users u');
        
        return $builder->select('u.*, GROUP_CONCAT(r.name) as roles, GROUP_CONCAT(r.id) as role_ids')
                      ->join('user_roles ur', 'ur.user_id = u.id', 'left')
                      ->join('roles r', 'r.id = ur.role_id', 'left')
                      ->where('u.id', $userId)
                      ->groupBy('u.id')
                      ->get()
                      ->getRowArray();
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions($userId)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('users u');
        
        return $builder->select('DISTINCT p.name as permission')
                      ->join('user_roles ur', 'ur.user_id = u.id')
                      ->join('role_permissions rp', 'rp.role_id = ur.role_id')
                      ->join('permissions p', 'p.id = rp.permission_id')
                      ->where('u.id', $userId)
                      ->get()
                      ->getResultArray();
    }

    /**
     * Get user with roles and permissions
     */
    public function getUserWithRolesAndPermissions($userId)
    {
        $user = $this->find($userId);
        
        if (!$user) {
            return null;
        }

        // Get user roles
        $roleModel = new RoleModel();
        $roles = $roleModel->select('roles.*, user_roles.user_id')
                          ->join('user_roles', 'user_roles.role_id = roles.id')
                          ->where('user_roles.user_id', $userId)
                          ->findAll();

        // Get user permissions through roles
        $permissions = [];
        if (!empty($roles)) {
            $roleIds = array_column($roles, 'id');
            $permissionModel = new PermissionModel();
            $permissions = $permissionModel->select('permissions.*, role_permissions.role_id')
                                          ->join('role_permissions', 'role_permissions.permission_id = permissions.id')
                                          ->whereIn('role_permissions.role_id', $roleIds)
                                          ->findAll();
        }

        $user['roles'] = $roles;
        $user['permissions'] = $permissions;

        return $user;
    }

    /**
     * Get users with roles
     */
    public function getUsersWithRoles()
    {
        return $this->select('users.*, GROUP_CONCAT(roles.name SEPARATOR ", ") as role_names')
                   ->join('user_roles', 'user_roles.user_id = users.id', 'left')
                   ->join('roles', 'roles.id = user_roles.role_id', 'left')
                   ->groupBy('users.id')
                   ->findAll();
    }

    /**
     * Assign roles to user
     */
    public function assignRoles($userId, $roleIds)
    {
        $db = \Config\Database::connect();
        
        // Delete existing roles
        $db->table('user_roles')->where('user_id', $userId)->delete();
        
        // Insert new roles
        if (!empty($roleIds)) {
            $data = [];
            foreach ($roleIds as $roleId) {
                $data[] = [
                    'user_id' => $userId,
                    'role_id' => $roleId
                ];
            }
            $db->table('user_roles')->insertBatch($data);
        }
        
        return true;
    }

    /**
     * Remove role from user
     */
    public function removeRole($userId, $roleId)
    {
        $db = \Config\Database::connect();
        return $db->table('user_roles')
                 ->where('user_id', $userId)
                 ->where('role_id', $roleId)
                 ->delete();
    }

    /**
     * Check if user has permission
     */
    public function hasPermission($userId, $permissionName)
    {
        $user = $this->getUserWithRolesAndPermissions($userId);
        
        if (!$user || empty($user['permissions'])) {
            return false;
        }
        
        foreach ($user['permissions'] as $permission) {
            if ($permission['name'] === $permissionName) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get users by role
     */
    public function getUsersByRole($roleId)
    {
        return $this->select('users.*')
                   ->join('user_roles', 'user_roles.user_id = users.id')
                   ->where('user_roles.role_id', $roleId)
                   ->findAll();
    }
}
