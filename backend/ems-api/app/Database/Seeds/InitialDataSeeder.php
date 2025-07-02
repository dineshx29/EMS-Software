<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    public function run()
    {
        // Create departments
        $departments = [
            ['name' => 'Human Resources', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'Information Technology', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'Finance', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'Marketing', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'Operations', 'created_at' => date('Y-m-d H:i:s')],
        ];

        $this->db->table('departments')->insertBatch($departments);

        // Create permissions
        $permissions = [
            // User Management
            ['name' => 'view_users', 'group_name' => 'User Management'],
            ['name' => 'create_users', 'group_name' => 'User Management'],
            ['name' => 'edit_users', 'group_name' => 'User Management'],
            ['name' => 'delete_users', 'group_name' => 'User Management'],
            
            // Employee Management
            ['name' => 'view_employees', 'group_name' => 'Employee Management'],
            ['name' => 'create_employees', 'group_name' => 'Employee Management'],
            ['name' => 'edit_employees', 'group_name' => 'Employee Management'],
            ['name' => 'delete_employees', 'group_name' => 'Employee Management'],
            
            // Department Management
            ['name' => 'view_departments', 'group_name' => 'Department Management'],
            ['name' => 'create_departments', 'group_name' => 'Department Management'],
            ['name' => 'edit_departments', 'group_name' => 'Department Management'],
            ['name' => 'delete_departments', 'group_name' => 'Department Management'],
            
            // Role Management
            ['name' => 'view_roles', 'group_name' => 'Role Management'],
            ['name' => 'create_roles', 'group_name' => 'Role Management'],
            ['name' => 'edit_roles', 'group_name' => 'Role Management'],
            ['name' => 'delete_roles', 'group_name' => 'Role Management'],
            
            // Permission Management
            ['name' => 'view_permissions', 'group_name' => 'Permission Management'],
            ['name' => 'create_permissions', 'group_name' => 'Permission Management'],
            ['name' => 'edit_permissions', 'group_name' => 'Permission Management'],
            ['name' => 'delete_permissions', 'group_name' => 'Permission Management'],
            
            // Dashboard & Reports
            ['name' => 'view_dashboard', 'group_name' => 'Dashboard & Reports'],
            ['name' => 'view_analytics', 'group_name' => 'Dashboard & Reports'],
            ['name' => 'view_activity_logs', 'group_name' => 'Dashboard & Reports'],
            
            // Notifications
            ['name' => 'view_notifications', 'group_name' => 'Notifications'],
            ['name' => 'send_notifications', 'group_name' => 'Notifications'],
            ['name' => 'manage_notifications', 'group_name' => 'Notifications'],
        ];

        $this->db->table('permissions')->insertBatch($permissions);

        // Create roles
        $roles = [
            [
                'name' => 'Super Admin', 
                'description' => 'Full system access with all permissions',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'HR Manager', 
                'description' => 'Human Resources management with employee access',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Department Manager', 
                'description' => 'Department-level management access',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Employee', 
                'description' => 'Basic employee access to view own information',
                'created_at' => date('Y-m-d H:i:s')
            ],
        ];

        $this->db->table('roles')->insertBatch($roles);

        // Get inserted role IDs
        $superAdminRole = $this->db->table('roles')->where('name', 'Super Admin')->get()->getRow();
        $hrManagerRole = $this->db->table('roles')->where('name', 'HR Manager')->get()->getRow();
        $deptManagerRole = $this->db->table('roles')->where('name', 'Department Manager')->get()->getRow();
        $employeeRole = $this->db->table('roles')->where('name', 'Employee')->get()->getRow();

        // Get permission IDs
        $allPermissions = $this->db->table('permissions')->get()->getResult();
        $permissionMap = [];
        foreach ($allPermissions as $permission) {
            $permissionMap[$permission->name] = $permission->id;
        }

        // Assign all permissions to Super Admin
        $superAdminPermissions = [];
        foreach ($allPermissions as $permission) {
            $superAdminPermissions[] = [
                'role_id' => $superAdminRole->id,
                'permission_id' => $permission->id
            ];
        }
        $this->db->table('role_permissions')->insertBatch($superAdminPermissions);

        // Assign permissions to HR Manager
        $hrPermissions = [
            'view_dashboard', 'view_employees', 'create_employees', 'edit_employees', 'delete_employees',
            'view_departments', 'view_users', 'create_users', 'edit_users', 'view_notifications',
            'send_notifications', 'view_activity_logs'
        ];
        $hrManagerPermissions = [];
        foreach ($hrPermissions as $permName) {
            if (isset($permissionMap[$permName])) {
                $hrManagerPermissions[] = [
                    'role_id' => $hrManagerRole->id,
                    'permission_id' => $permissionMap[$permName]
                ];
            }
        }
        $this->db->table('role_permissions')->insertBatch($hrManagerPermissions);

        // Assign permissions to Department Manager
        $deptPermissions = [
            'view_dashboard', 'view_employees', 'edit_employees', 'view_departments',
            'view_notifications', 'view_activity_logs'
        ];
        $deptManagerPermissions = [];
        foreach ($deptPermissions as $permName) {
            if (isset($permissionMap[$permName])) {
                $deptManagerPermissions[] = [
                    'role_id' => $deptManagerRole->id,
                    'permission_id' => $permissionMap[$permName]
                ];
            }
        }
        $this->db->table('role_permissions')->insertBatch($deptManagerPermissions);

        // Assign permissions to Employee
        $empPermissions = ['view_dashboard', 'view_notifications'];
        $employeePermissions = [];
        foreach ($empPermissions as $permName) {
            if (isset($permissionMap[$permName])) {
                $employeePermissions[] = [
                    'role_id' => $employeeRole->id,
                    'permission_id' => $permissionMap[$permName]
                ];
            }
        }
        $this->db->table('role_permissions')->insertBatch($employeePermissions);

        // Create default users
        $users = [
            [
                'username' => 'admin',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'email' => 'admin@ems.com',
                'full_name' => 'System Administrator',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'username' => 'hrmanager',
                'password' => password_hash('hr123', PASSWORD_DEFAULT),
                'email' => 'hr@ems.com',
                'full_name' => 'HR Manager',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'username' => 'deptmanager',
                'password' => password_hash('dept123', PASSWORD_DEFAULT),
                'email' => 'dept@ems.com',
                'full_name' => 'Department Manager',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ],
        ];

        $this->db->table('users')->insertBatch($users);

        // Get user IDs and assign roles
        $adminUser = $this->db->table('users')->where('username', 'admin')->get()->getRow();
        $hrUser = $this->db->table('users')->where('username', 'hrmanager')->get()->getRow();
        $deptUser = $this->db->table('users')->where('username', 'deptmanager')->get()->getRow();

        $userRoles = [
            ['user_id' => $adminUser->id, 'role_id' => $superAdminRole->id],
            ['user_id' => $hrUser->id, 'role_id' => $hrManagerRole->id],
            ['user_id' => $deptUser->id, 'role_id' => $deptManagerRole->id],
        ];

        $this->db->table('user_roles')->insertBatch($userRoles);

        // Create sample employees
        $itDept = $this->db->table('departments')->where('name', 'Information Technology')->get()->getRow();
        $hrDept = $this->db->table('departments')->where('name', 'Human Resources')->get()->getRow();
        $financeDept = $this->db->table('departments')->where('name', 'Finance')->get()->getRow();

        $employees = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@ems.com',
                'phone' => '+1234567890',
                'address' => '123 Main St, City, State',
                'job_title' => 'Software Developer',
                'department_id' => $itDept->id,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@ems.com',
                'phone' => '+1234567891',
                'address' => '456 Oak Ave, City, State',
                'job_title' => 'HR Specialist',
                'department_id' => $hrDept->id,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'first_name' => 'Mike',
                'last_name' => 'Johnson',
                'email' => 'mike.johnson@ems.com',
                'phone' => '+1234567892',
                'address' => '789 Pine St, City, State',
                'job_title' => 'Financial Analyst',
                'department_id' => $financeDept->id,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Wilson',
                'email' => 'sarah.wilson@ems.com',
                'phone' => '+1234567893',
                'address' => '321 Elm St, City, State',
                'job_title' => 'Senior Developer',
                'department_id' => $itDept->id,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
        ];

        $this->db->table('employees')->insertBatch($employees);

        // Create welcome notifications for users
        $notifications = [
            [
                'user_id' => $adminUser->id,
                'type' => 'info',
                'message' => 'Welcome to the Employee Management System! You have full admin access.',
                'is_read' => false,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'user_id' => $hrUser->id,
                'type' => 'info',
                'message' => 'Welcome to the Employee Management System! You have HR Manager access.',
                'is_read' => false,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'user_id' => $deptUser->id,
                'type' => 'info',
                'message' => 'Welcome to the Employee Management System! You have Department Manager access.',
                'is_read' => false,
                'created_at' => date('Y-m-d H:i:s')
            ],
        ];

        $this->db->table('notifications')->insertBatch($notifications);

        // Create sample activity logs
        $activityLogs = [
            [
                'user_id' => $adminUser->id,
                'action' => 'System initialized with seed data',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'user_id' => $hrUser->id,
                'action' => 'HR Manager account created',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'user_id' => $deptUser->id,
                'action' => 'Department Manager account created',
                'created_at' => date('Y-m-d H:i:s')
            ],
        ];

        $this->db->table('activity_logs')->insertBatch($activityLogs);

        echo "Initial data seeded successfully!\n";
        echo "Default Users:\n";
        echo "- Admin: admin / admin123\n";
        echo "- HR Manager: hrmanager / hr123\n";
        echo "- Department Manager: deptmanager / dept123\n";
    }
}
