<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use App\Models\DepartmentModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class EmployeeController extends ResourceController
{
    use ResponseTrait;

    protected $employeeModel;
    protected $departmentModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->departmentModel = new DepartmentModel();
        helper('jwt');
    }

    /**
     * Return an array of resource objects, themselves in array format
     */
    public function index()
    {
        try {
            $employees = $this->employeeModel->getEmployeesWithDepartment();
            
            return $this->respond([
                'status' => 'success',
                'data' => $employees
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch employees: ' . $e->getMessage());
        }
    }

    /**
     * Return the properties of a resource object
     */
    public function show($id = null)
    {
        try {
            $employee = $this->employeeModel->getEmployeeWithDepartment($id);
            
            if (!$employee) {
                return $this->failNotFound('Employee not found');
            }

            return $this->respond([
                'status' => 'success',
                'data' => $employee
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch employee: ' . $e->getMessage());
        }
    }

    /**
     * Create a new resource object, from "posted" parameters
     */
    public function create()
    {
        $rules = [
            'first_name' => 'required|min_length[2]|max_length[50]',
            'last_name' => 'required|min_length[2]|max_length[50]',
            'email' => 'required|valid_email|is_unique[employees.email]',
            'phone' => 'permit_empty|max_length[20]',
            'address' => 'permit_empty|max_length[255]',
            'job_title' => 'permit_empty|max_length[100]',
            'department_id' => 'permit_empty|integer|is_not_unique[departments.id]',
            'status' => 'permit_empty|in_list[active,inactive,scheduled]',
            'scheduled_activation_date' => 'permit_empty|valid_date'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'first_name' => $this->request->getPost('first_name'),
            'last_name' => $this->request->getPost('last_name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
            'address' => $this->request->getPost('address'),
            'job_title' => $this->request->getPost('job_title'),
            'department_id' => $this->request->getPost('department_id') ?: null,
            'status' => $this->request->getPost('status') ?: 'active',
            'scheduled_activation_date' => $this->request->getPost('scheduled_activation_date') ?: null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            $id = $this->employeeModel->insert($data);
            
            if ($id) {
                $employee = $this->employeeModel->getEmployeeWithDepartment($id);
                return $this->respondCreated([
                    'status' => 'success',
                    'message' => 'Employee created successfully',
                    'data' => $employee
                ]);
            } else {
                return $this->fail('Failed to create employee');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to create employee: ' . $e->getMessage());
        }
    }

    /**
     * Add or update a model resource, from "posted" properties
     */
    public function update($id = null)
    {
        $employee = $this->employeeModel->find($id);
        
        if (!$employee) {
            return $this->failNotFound('Employee not found');
        }

        $rules = [
            'first_name' => 'required|min_length[2]|max_length[50]',
            'last_name' => 'required|min_length[2]|max_length[50]',
            'email' => "required|valid_email|is_unique[employees.email,id,{$id}]",
            'phone' => 'permit_empty|max_length[20]',
            'address' => 'permit_empty|max_length[255]',
            'job_title' => 'permit_empty|max_length[100]',
            'department_id' => 'permit_empty|integer|is_not_unique[departments.id]',
            'status' => 'permit_empty|in_list[active,inactive,scheduled]',
            'scheduled_activation_date' => 'permit_empty|valid_date'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $inputData = $this->request->getRawInput() ?: $this->request->getPost();
        
        $data = [
            'first_name' => $inputData['first_name'],
            'last_name' => $inputData['last_name'],
            'email' => $inputData['email'],
            'phone' => $inputData['phone'] ?? null,
            'address' => $inputData['address'] ?? null,
            'job_title' => $inputData['job_title'] ?? null,
            'department_id' => $inputData['department_id'] ?? null,
            'status' => $inputData['status'] ?? 'active',
            'scheduled_activation_date' => $inputData['scheduled_activation_date'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            $this->employeeModel->update($id, $data);
            
            $updatedEmployee = $this->employeeModel->getEmployeeWithDepartment($id);
            return $this->respond([
                'status' => 'success',
                'message' => 'Employee updated successfully',
                'data' => $updatedEmployee
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to update employee: ' . $e->getMessage());
        }
    }

    /**
     * Delete the designated resource object from the model
     */
    public function delete($id = null)
    {
        $employee = $this->employeeModel->find($id);
        
        if (!$employee) {
            return $this->failNotFound('Employee not found');
        }

        try {
            $this->employeeModel->delete($id);
            
            return $this->respondDeleted([
                'status' => 'success',
                'message' => 'Employee deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to delete employee: ' . $e->getMessage());
        }
    }

    /**
     * Get employees with pagination and filtering
     */
    public function paginated()
    {
        $page = $this->request->getGet('page') ?? 1;
        $perPage = $this->request->getGet('per_page') ?? 10;
        $search = $this->request->getGet('search') ?? '';
        $department = $this->request->getGet('department') ?? '';
        $status = $this->request->getGet('status') ?? '';

        try {
            $employees = $this->employeeModel->getEmployeesPaginated($page, $perPage, $search, $department, $status);

            return $this->respond([
                'status' => 'success',
                'data' => $employees['data'],
                'pagination' => $employees['pagination']
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch employees: ' . $e->getMessage());
        }
    }

    /**
     * Get employees by department
     */
    public function byDepartment($departmentId = null)
    {
        try {
            $employees = $this->employeeModel->where('department_id', $departmentId)->findAll();
            
            return $this->respond([
                'status' => 'success',
                'data' => $employees
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch employees: ' . $e->getMessage());
        }
    }

    /**
     * Update employee status
     */
    public function updateStatus($id = null)
    {
        $employee = $this->employeeModel->find($id);
        
        if (!$employee) {
            return $this->failNotFound('Employee not found');
        }

        $rules = [
            'status' => 'required|in_list[active,inactive,scheduled]',
            'scheduled_activation_date' => 'permit_empty|valid_date'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $inputData = $this->request->getRawInput() ?: $this->request->getPost();
        
        $data = [
            'status' => $inputData['status'],
            'scheduled_activation_date' => $inputData['scheduled_activation_date'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            $this->employeeModel->update($id, $data);
            
            $updatedEmployee = $this->employeeModel->getEmployeeWithDepartment($id);
            return $this->respond([
                'status' => 'success',
                'message' => 'Employee status updated successfully',
                'data' => $updatedEmployee
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to update employee status: ' . $e->getMessage());
        }
    }
}
