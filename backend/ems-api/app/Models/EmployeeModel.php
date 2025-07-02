<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeModel extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'first_name', 'last_name', 'email', 'phone', 'address', 
        'job_title', 'department_id', 'status', 'scheduled_activation_date',
        'created_at', 'updated_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = '';

    // Validation
    protected $validationRules = [
        'first_name' => 'required|max_length[50]',
        'last_name' => 'required|max_length[50]',
        'email' => 'required|valid_email|is_unique[employees.email,id,{id}]',
        'phone' => 'max_length[20]',
        'address' => 'max_length[255]',
        'job_title' => 'max_length[100]',
        'department_id' => 'permit_empty|is_natural_no_zero',
        'status' => 'in_list[active,inactive,scheduled]',
        'scheduled_activation_date' => 'permit_empty|valid_date'
    ];

    protected $validationMessages = [
        'first_name' => [
            'required' => 'First name is required',
            'max_length' => 'First name cannot exceed 50 characters'
        ],
        'last_name' => [
            'required' => 'Last name is required',
            'max_length' => 'Last name cannot exceed 50 characters'
        ],
        'email' => [
            'required' => 'Email is required',
            'valid_email' => 'Please enter a valid email address',
            'is_unique' => 'Email already exists'
        ],
        'phone' => [
            'max_length' => 'Phone cannot exceed 20 characters'
        ],
        'status' => [
            'in_list' => 'Status must be active, inactive, or scheduled'
        ],
        'department_id' => [
            'is_natural_no_zero' => 'Please select a valid department'
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
     * Get all employees with department information
     */
    public function getEmployeesWithDepartment()
    {
        return $this->select('employees.*, departments.name as department_name')
                   ->join('departments', 'departments.id = employees.department_id', 'left')
                   ->orderBy('employees.created_at', 'DESC')
                   ->findAll();
    }

    /**
     * Get employee by ID with department information
     */
    public function getEmployeeWithDepartment($employeeId)
    {
        return $this->select('employees.*, departments.name as department_name')
                   ->join('departments', 'departments.id = employees.department_id', 'left')
                   ->where('employees.id', $employeeId)
                   ->first();
    }

    /**
     * Get employees paginated with optional search, department, and status filters
     */
    public function getEmployeesPaginated($page, $perPage, $search = '', $department = '', $status = '')
    {
        $builder = $this->select('employees.*, departments.name as department_name')
                       ->join('departments', 'departments.id = employees.department_id', 'left');

        if (!empty($search)) {
            $builder = $builder->groupStart()
                ->like('employees.first_name', $search)
                ->orLike('employees.last_name', $search)
                ->orLike('employees.email', $search)
                ->orLike('employees.job_title', $search)
                ->groupEnd();
        }

        if (!empty($department)) {
            $builder = $builder->where('employees.department_id', $department);
        }

        if (!empty($status)) {
            $builder = $builder->where('employees.status', $status);
        }

        $employees = $builder->orderBy('employees.created_at', 'DESC')
                           ->paginate($perPage, 'default', $page);
        
        $pager = $this->pager;

        return [
            'data' => $employees,
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'per_page' => $pager->getPerPage(),
                'total' => $pager->getTotal(),
                'total_pages' => $pager->getPageCount()
            ]
        ];
    }

    /**
     * Get employees by department
     */
    public function getEmployeesByDepartment($departmentId)
    {
        return $this->where('department_id', $departmentId)
                   ->orderBy('first_name', 'ASC')
                   ->findAll();
    }

    /**
     * Get active employees
     */
    public function getActiveEmployees()
    {
        return $this->where('status', 'active')
                   ->orderBy('first_name', 'ASC')
                   ->findAll();
    }

    /**
     * Get scheduled employees
     */
    public function getScheduledEmployees()
    {
        return $this->where('status', 'scheduled')
                   ->where('scheduled_activation_date <=', date('Y-m-d'))
                   ->findAll();
    }

    /**
     * Activate scheduled employees
     */
    public function activateScheduledEmployees()
    {
        return $this->where('status', 'scheduled')
                   ->where('scheduled_activation_date <=', date('Y-m-d'))
                   ->set(['status' => 'active', 'scheduled_activation_date' => null])
                   ->update();
    }

    /**
     * Get employee statistics
     */
    public function getEmployeeStatistics()
    {
        $total = $this->countAll();
        $active = $this->where('status', 'active')->countAllResults();
        $inactive = $this->where('status', 'inactive')->countAllResults();
        $scheduled = $this->where('status', 'scheduled')->countAllResults();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'scheduled' => $scheduled
        ];
    }
}
