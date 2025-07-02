<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartmentModel extends Model
{
    protected $table = 'departments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['name', 'created_at'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $deletedField = '';

    // Validation
    protected $validationRules = [
        'name' => 'required|max_length[100]|is_unique[departments.name,id,{id}]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Department name is required',
            'max_length' => 'Department name cannot exceed 100 characters',
            'is_unique' => 'Department name already exists'
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
     * Get all departments with employee count
     */
    public function getDepartmentsWithEmployeeCount()
    {
        return $this->select('departments.*, COUNT(employees.id) as employee_count')
                    ->join('employees', 'employees.department_id = departments.id', 'left')
                    ->groupBy('departments.id')
                    ->findAll();
    }

    /**
     * Get department by ID with employee count
     */
    public function getDepartmentWithEmployeeCount($id)
    {
        return $this->select('departments.*, COUNT(employees.id) as employee_count')
                    ->join('employees', 'employees.department_id = departments.id', 'left')
                    ->where('departments.id', $id)
                    ->groupBy('departments.id')
                    ->first();
    }
}
