<?php

namespace App\Controllers;

use App\Models\DepartmentModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class DepartmentController extends ResourceController
{
    use ResponseTrait;

    protected $departmentModel;

    public function __construct()
    {
        $this->departmentModel = new DepartmentModel();
        helper('jwt');
    }

    /**
     * Return an array of resource objects, themselves in array format
     */
    public function index()
    {
        try {
            $departments = $this->departmentModel->getDepartmentsWithEmployeeCount();
            
            return $this->respond([
                'status' => 'success',
                'data' => $departments
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch departments: ' . $e->getMessage());
        }
    }

    /**
     * Return the properties of a resource object
     */
    public function show($id = null)
    {
        try {
            $department = $this->departmentModel->find($id);
            
            if (!$department) {
                return $this->failNotFound('Department not found');
            }

            return $this->respond([
                'status' => 'success',
                'data' => $department
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch department: ' . $e->getMessage());
        }
    }

    /**
     * Create a new resource object, from "posted" parameters
     */
    public function create()
    {
        $rules = [
            'name' => 'required|min_length[2]|max_length[100]|is_unique[departments.name]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            $id = $this->departmentModel->insert($data);
            
            if ($id) {
                $department = $this->departmentModel->find($id);
                return $this->respondCreated([
                    'status' => 'success',
                    'message' => 'Department created successfully',
                    'data' => $department
                ]);
            } else {
                return $this->fail('Failed to create department');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to create department: ' . $e->getMessage());
        }
    }

    /**
     * Add or update a model resource, from "posted" properties
     */
    public function update($id = null)
    {
        $department = $this->departmentModel->find($id);
        
        if (!$department) {
            return $this->failNotFound('Department not found');
        }

        $rules = [
            'name' => "required|min_length[2]|max_length[100]|is_unique[departments.name,id,{$id}]"
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getRawInput()['name'] ?? $this->request->getPost('name')
        ];

        try {
            $this->departmentModel->update($id, $data);
            
            $updatedDepartment = $this->departmentModel->find($id);
            return $this->respond([
                'status' => 'success',
                'message' => 'Department updated successfully',
                'data' => $updatedDepartment
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to update department: ' . $e->getMessage());
        }
    }

    /**
     * Delete the designated resource object from the model
     */
    public function delete($id = null)
    {
        $department = $this->departmentModel->find($id);
        
        if (!$department) {
            return $this->failNotFound('Department not found');
        }

        try {
            // Check if department has employees
            $employeeModel = new \App\Models\EmployeeModel();
            $employeeCount = $employeeModel->where('department_id', $id)->countAllResults();
            
            if ($employeeCount > 0) {
                return $this->fail('Cannot delete department with existing employees');
            }

            $this->departmentModel->delete($id);
            
            return $this->respondDeleted([
                'status' => 'success',
                'message' => 'Department deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to delete department: ' . $e->getMessage());
        }
    }

    /**
     * Get departments with pagination
     */
    public function paginated()
    {
        $page = $this->request->getGet('page') ?? 1;
        $perPage = $this->request->getGet('per_page') ?? 10;
        $search = $this->request->getGet('search') ?? '';

        try {
            $builder = $this->departmentModel;
            
            if (!empty($search)) {
                $builder = $builder->like('name', $search);
            }

            $departments = $builder->paginate($perPage, 'default', $page);
            $pager = $this->departmentModel->pager;

            return $this->respond([
                'status' => 'success',
                'data' => $departments,
                'pagination' => [
                    'current_page' => $pager->getCurrentPage(),
                    'per_page' => $pager->getPerPage(),
                    'total' => $pager->getTotal(),
                    'total_pages' => $pager->getPageCount()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch departments: ' . $e->getMessage());
        }
    }
}
