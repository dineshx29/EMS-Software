import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { NotificationService } from '../../services/notification.service';

interface Employee {
  id: number;
  employeeId: string;
  firstName: string;
  lastName: string;
  email: string;
  phone?: string;
  dateOfBirth?: string;
  gender?: string;
  department: string;
  position: string;
  managerId?: number;
  manager?: string;
  hireDate?: string;
  joinDate?: string;
  employmentType?: string;
  salary?: number;
  status: string;
  streetAddress?: string;
  city?: string;
  state?: string;
  zipCode?: string;
  country?: string;
  notes?: string;
  emergencyContactName?: string;
  emergencyContactPhone?: string;
  emergencyContactRelationship?: string;
  createdAt?: string;
  updatedAt?: string;
}

@Component({
  selector: 'app-employees',
  standalone: true,
  imports: [CommonModule, FormsModule, ReactiveFormsModule],
  templateUrl: './employees.component.html',
  styleUrl: './employees.component.scss'
})
export class EmployeesComponent implements OnInit {
  private fb = inject(FormBuilder);
  private notificationService = inject(NotificationService);

  // Data properties
  employees: Employee[] = [];
  departments: string[] = [];
  positions: string[] = [];
  managers: Employee[] = [];

  // Status options for dropdown
  statuses: string[] = ['Active', 'Inactive', 'On Leave', 'Terminated'];

  // UI state
  showAddModal = false;
  showModal = false; // For the view/edit modal
  isEditing = false;
  viewMode = false; // View-only mode
  isLoading = false;
  selectedEmployee: Employee | null = null;

  // Form
  employeeForm!: FormGroup;

  // Search and filtering
  searchTerm = '';
  selectedDepartment = '';
  selectedStatus = '';

  // Pagination
  currentPage = 1;
  itemsPerPage = 10;
  totalItems = 0;
  totalPages = 0;
  paginatedEmployees: Employee[] = [];

  // Utility property for template
  Math = Math;

  constructor() {
    this.initializeForm();
  }

  ngOnInit(): void {
    this.loadMockData();
    this.updatePagination();
  }

  private initializeForm(): void {
    this.employeeForm = this.fb.group({
      employeeId: ['', Validators.required],
      firstName: ['', [Validators.required, Validators.minLength(2)]],
      lastName: ['', [Validators.required, Validators.minLength(2)]],
      email: ['', [Validators.required, Validators.email]],
      phone: [''],
      dateOfBirth: [''],
      gender: [''],
      department: ['', Validators.required],
      position: ['', Validators.required],
      managerId: [''],
      hireDate: ['', Validators.required],
      employmentType: ['', Validators.required],
      salary: [''],
      status: ['Active', Validators.required],
      streetAddress: [''],
      city: [''],
      state: [''],
      zipCode: [''],
      country: [''],
      notes: [''],
      emergencyContactName: [''],
      emergencyContactPhone: [''],
      emergencyContactRelationship: ['']
    });
  }

  private loadMockData(): void {
    // Mock departments and positions
    this.departments = [
      'Information Technology',
      'Human Resources',
      'Finance',
      'Marketing',
      'Sales',
      'Operations',
      'Legal',
      'Customer Service'
    ];

    this.positions = [
      'Software Engineer',
      'Senior Software Engineer',
      'Team Lead',
      'Project Manager',
      'HR Specialist',
      'Financial Analyst',
      'Marketing Specialist',
      'Sales Representative',
      'Operations Manager',
      'Legal Counsel'
    ];

    // Mock employees data
    this.employees = [
      {
        id: 1,
        employeeId: 'EMP001',
        firstName: 'John',
        lastName: 'Smith',
        email: 'john.smith@company.com',
        phone: '+1 (555) 123-4567',
        dateOfBirth: '1990-05-15',
        gender: 'Male',
        department: 'Information Technology',
        position: 'Senior Software Engineer',
        managerId: 2,
        manager: 'Jane Doe',
        hireDate: '2022-01-15',
        employmentType: 'Full-time',
        salary: 85000,
        status: 'Active',
        streetAddress: '123 Main St',
        city: 'New York',
        state: 'NY',
        zipCode: '10001',
        country: 'USA',
        notes: 'Excellent performer',
        emergencyContactName: 'Mary Smith',
        emergencyContactPhone: '+1 (555) 987-6543',
        emergencyContactRelationship: 'Spouse'
      },
      {
        id: 2,
        employeeId: 'EMP002',
        firstName: 'Jane',
        lastName: 'Doe',
        email: 'jane.doe@company.com',
        phone: '+1 (555) 234-5678',
        dateOfBirth: '1985-03-22',
        gender: 'Female',
        department: 'Information Technology',
        position: 'Team Lead',
        hireDate: '2020-06-01',
        employmentType: 'Full-time',
        salary: 95000,
        status: 'Active',
        streetAddress: '456 Oak Ave',
        city: 'San Francisco',
        state: 'CA',
        zipCode: '94102',
        country: 'USA'
      },
      {
        id: 3,
        employeeId: 'EMP003',
        firstName: 'Mike',
        lastName: 'Johnson',
        email: 'mike.johnson@company.com',
        phone: '+1 (555) 345-6789',
        dateOfBirth: '1992-11-08',
        gender: 'Male',
        department: 'Marketing',
        position: 'Marketing Specialist',
        managerId: 4,
        hireDate: '2023-03-10',
        employmentType: 'Full-time',
        salary: 65000,
        status: 'Active',
        streetAddress: '789 Pine St',
        city: 'Chicago',
        state: 'IL',
        zipCode: '60601',
        country: 'USA'
      }
    ];

    // Set managers (employees who can be managers)
    this.managers = this.employees.filter(emp =>
      ['Team Lead', 'Project Manager', 'Operations Manager'].includes(emp.position)
    );

    this.totalItems = this.employees.length;
    this.filterEmployees();
  }

  // Search and filtering methods
  onSearchChange(): void {
    this.currentPage = 1;
    this.filterEmployees();
  }

  onDepartmentChange(): void {
    this.selectedDepartment = this.selectedDepartment;
    this.currentPage = 1;
    this.filterEmployees();
  }

  onStatusChange(): void {
    this.selectedStatus = this.selectedStatus;
    this.currentPage = 1;
    this.filterEmployees();
  }

  clearFilters(): void {
    this.searchTerm = '';
    this.selectedDepartment = '';
    this.selectedStatus = '';
    this.currentPage = 1;
    this.filterEmployees();
  }

  private filterEmployees(): void {
    let filtered = [...this.employees];

    // Apply search filter
    if (this.searchTerm) {
      const term = this.searchTerm.toLowerCase();
      filtered = filtered.filter(emp =>
        emp.firstName.toLowerCase().includes(term) ||
        emp.lastName.toLowerCase().includes(term) ||
        emp.email.toLowerCase().includes(term) ||
        emp.employeeId.toLowerCase().includes(term)
      );
    }

    // Apply department filter
    if (this.selectedDepartment) {
      filtered = filtered.filter(emp => emp.department === this.selectedDepartment);
    }

    // Apply status filter
    if (this.selectedStatus) {
      filtered = filtered.filter(emp => emp.status === this.selectedStatus);
    }

    this.totalItems = filtered.length;
    this.updatePagination();

    // Apply pagination
    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    const endIndex = startIndex + this.itemsPerPage;
    this.paginatedEmployees = filtered.slice(startIndex, endIndex);
  }

  private updatePagination(): void {
    this.totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
    if (this.currentPage > this.totalPages) {
      this.currentPage = Math.max(1, this.totalPages);
    }
  }

  // Pagination methods
  previousPage(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
      this.filterEmployees();
    }
  }

  nextPage(): void {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
      this.filterEmployees();
    }
  }

  // Modal methods
  addEmployee(): void {
    this.isEditing = false;
    this.selectedEmployee = null;
    this.resetForm();
    this.showAddModal = true;
  }

  viewEmployee(employee: Employee): void {
    this.selectedEmployee = { ...employee };
    this.viewMode = true;
    this.isEditing = false;
    this.showModal = true;
    this.populateForm(employee);
  }

  editEmployee(employee: Employee): void {
    this.selectedEmployee = { ...employee };
    this.viewMode = false;
    this.isEditing = true;
    this.showModal = true;
    this.populateForm(employee);
  }

  closeModal(): void {
    this.showAddModal = false;
    this.showModal = false;
    this.selectedEmployee = null;
    this.isEditing = false;
    this.viewMode = false;
    this.employeeForm.reset();
  }

  private resetForm(): void {
    this.employeeForm.reset({
      status: 'Active',
      employmentType: 'Full-time',
      country: 'USA'
    });
  }

  private populateForm(employee: Employee): void {
    this.employeeForm.patchValue({
      employeeId: employee.employeeId,
      firstName: employee.firstName,
      lastName: employee.lastName,
      email: employee.email,
      phone: employee.phone,
      dateOfBirth: employee.dateOfBirth,
      gender: employee.gender,
      department: employee.department,
      position: employee.position,
      managerId: employee.managerId,
      hireDate: employee.hireDate,
      employmentType: employee.employmentType,
      salary: employee.salary,
      status: employee.status,
      streetAddress: employee.streetAddress,
      city: employee.city,
      state: employee.state,
      zipCode: employee.zipCode,
      country: employee.country,
      notes: employee.notes,
      emergencyContactName: employee.emergencyContactName,
      emergencyContactPhone: employee.emergencyContactPhone,
      emergencyContactRelationship: employee.emergencyContactRelationship
    });
  }

  // Form validation helper
  isFieldInvalid(fieldName: string): boolean {
    const field = this.employeeForm.get(fieldName);
    return !!(field && field.invalid && (field.dirty || field.touched));
  }

  // CRUD operations
  saveEmployee(): void {
    if (this.employeeForm.valid) {
      this.isLoading = true;

      // Simulate API call
      setTimeout(() => {
        try {
          const formValue = this.employeeForm.value;

          if (this.isEditing && this.selectedEmployee) {
            // Update existing employee
            const empIndex = this.employees.findIndex(e => e.id === this.selectedEmployee!.id);
            if (empIndex !== -1) {
              this.employees[empIndex] = {
                ...this.employees[empIndex],
                ...formValue,
                updatedAt: new Date().toISOString()
              };

              this.notificationService.showSuccess('Success', 'Employee updated successfully');
            }
          } else {
            // Create new employee
            const newEmployee: Employee = {
              id: Math.max(...this.employees.map(e => e.id)) + 1,
              ...formValue,
              createdAt: new Date().toISOString(),
              updatedAt: new Date().toISOString()
            };

            this.employees.push(newEmployee);
            this.notificationService.showSuccess('Success', 'Employee created successfully');
          }

          this.filterEmployees();
          this.closeModal();
        } catch (error) {
          this.notificationService.showError('Error', 'Failed to save employee');
        } finally {
          this.isLoading = false;
        }
      }, 1000);
    } else {
      this.notificationService.showWarning('Warning', 'Please fill in all required fields');
    }
  }

  deleteEmployee(employee: Employee): void {
    if (confirm(`Are you sure you want to delete ${employee.firstName} ${employee.lastName}? This action cannot be undone.`)) {
      const index = this.employees.findIndex(e => e.id === employee.id);
      if (index !== -1) {
        this.employees.splice(index, 1);
        this.notificationService.showSuccess('Success', 'Employee deleted successfully');
        this.filterEmployees();
      }
    }
  }

  // Utility methods
  getStatusClass(status: string): string {
    const statusClasses: { [key: string]: string } = {
      'Active': 'status-active',
      'Inactive': 'status-inactive',
      'On Leave': 'status-on-leave',
      'Terminated': 'status-terminated'
    };
    return statusClasses[status] || 'status-default';
  }

  formatDate(dateString: string | undefined): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  }

  formatCurrency(amount: number | undefined): string {
    if (!amount) return '-';
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount);
  }

  getManagerName(managerId: number | undefined): string {
    if (!managerId) return '-';
    const manager = this.employees.find(emp => emp.id === managerId);
    return manager ? `${manager.firstName} ${manager.lastName}` : '-';
  }
}
