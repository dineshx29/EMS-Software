import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-employees',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './employees.component.html',
  styleUrl: './employees.component.scss'
})
export class EmployeesComponent implements OnInit {

  // Employee data
  employees = [
    {
      id: 1,
      employeeId: 'EMP001',
      firstName: 'John',
      lastName: 'Smith',
      email: 'john.smith@company.com',
      phone: '+1 (555) 123-4567',
      department: 'Engineering',
      position: 'Senior Developer',
      status: 'Active',
      joinDate: '2022-01-15',
      salary: 85000,
      manager: 'Sarah Johnson'
    },
    {
      id: 2,
      employeeId: 'EMP002',
      firstName: 'Sarah',
      lastName: 'Johnson',
      email: 'sarah.johnson@company.com',
      phone: '+1 (555) 234-5678',
      department: 'Engineering',
      position: 'Engineering Manager',
      status: 'Active',
      joinDate: '2021-03-10',
      salary: 120000,
      manager: 'Michael Brown'
    },
    {
      id: 3,
      employeeId: 'EMP003',
      firstName: 'Michael',
      lastName: 'Brown',
      email: 'michael.brown@company.com',
      phone: '+1 (555) 345-6789',
      department: 'Human Resources',
      position: 'HR Director',
      status: 'Active',
      joinDate: '2020-08-22',
      salary: 95000,
      manager: 'Emily Davis'
    },
    {
      id: 4,
      employeeId: 'EMP004',
      firstName: 'Emily',
      lastName: 'Davis',
      email: 'emily.davis@company.com',
      phone: '+1 (555) 456-7890',
      department: 'Sales',
      position: 'Sales Representative',
      status: 'Active',
      joinDate: '2023-02-14',
      salary: 65000,
      manager: 'John Smith'
    },
    {
      id: 5,
      employeeId: 'EMP005',
      firstName: 'Robert',
      lastName: 'Wilson',
      email: 'robert.wilson@company.com',
      phone: '+1 (555) 567-8901',
      department: 'Finance',
      position: 'Financial Analyst',
      status: 'Inactive',
      joinDate: '2021-11-05',
      salary: 70000,
      manager: 'Sarah Johnson'
    }
  ];

  // Filtered employees for display
  filteredEmployees = [...this.employees];

  // Search and filter properties
  searchTerm = '';
  selectedDepartment = '';
  selectedStatus = '';

  // Department list
  departments = ['Engineering', 'Human Resources', 'Sales', 'Finance', 'Marketing', 'Operations'];

  // Status list
  statuses = ['Active', 'Inactive', 'On Leave'];

  // Modal properties
  showModal = false;
  modalMode: 'add' | 'edit' | 'view' = 'add';
  selectedEmployee: any = {};

  // Pagination
  currentPage = 1;
  itemsPerPage = 10;
  totalItems = this.employees.length;

  constructor() { }

  ngOnInit(): void {
    this.applyFilters();
  }

  // Search and filter methods
  applyFilters(): void {
    this.filteredEmployees = this.employees.filter(emp => {
      const matchesSearch = !this.searchTerm || 
        emp.firstName.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        emp.lastName.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        emp.email.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        emp.employeeId.toLowerCase().includes(this.searchTerm.toLowerCase());

      const matchesDepartment = !this.selectedDepartment || emp.department === this.selectedDepartment;
      const matchesStatus = !this.selectedStatus || emp.status === this.selectedStatus;

      return matchesSearch && matchesDepartment && matchesStatus;
    });

    this.totalItems = this.filteredEmployees.length;
    this.currentPage = 1;
  }

  onSearchChange(): void {
    this.applyFilters();
  }

  onDepartmentChange(): void {
    this.applyFilters();
  }

  onStatusChange(): void {
    this.applyFilters();
  }

  // CRUD operations
  addEmployee(): void {
    this.modalMode = 'add';
    this.selectedEmployee = {
      firstName: '',
      lastName: '',
      email: '',
      phone: '',
      department: '',
      position: '',
      status: 'Active',
      joinDate: '',
      salary: 0,
      manager: ''
    };
    this.showModal = true;
  }

  editEmployee(employee: any): void {
    this.modalMode = 'edit';
    this.selectedEmployee = { ...employee };
    this.showModal = true;
  }

  viewEmployee(employee: any): void {
    this.modalMode = 'view';
    this.selectedEmployee = { ...employee };
    this.showModal = true;
  }

  deleteEmployee(employee: any): void {
    if (confirm(`Are you sure you want to delete ${employee.firstName} ${employee.lastName}?`)) {
      const index = this.employees.findIndex(emp => emp.id === employee.id);
      if (index > -1) {
        this.employees.splice(index, 1);
        this.applyFilters();
      }
    }
  }

  saveEmployee(): void {
    if (this.modalMode === 'add') {
      // Generate new ID and employee ID
      const newId = Math.max(...this.employees.map(emp => emp.id)) + 1;
      const newEmpId = `EMP${newId.toString().padStart(3, '0')}`;
      
      this.selectedEmployee.id = newId;
      this.selectedEmployee.employeeId = newEmpId;
      this.employees.push({ ...this.selectedEmployee });
    } else if (this.modalMode === 'edit') {
      const index = this.employees.findIndex(emp => emp.id === this.selectedEmployee.id);
      if (index > -1) {
        this.employees[index] = { ...this.selectedEmployee };
      }
    }

    this.closeModal();
    this.applyFilters();
  }

  closeModal(): void {
    this.showModal = false;
    this.selectedEmployee = {};
  }

  // Utility methods
  getStatusClass(status: string): string {
    switch (status.toLowerCase()) {
      case 'active': return 'status-active';
      case 'inactive': return 'status-inactive';
      case 'on leave': return 'status-leave';
      default: return 'status-default';
    }
  }

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount);
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US');
  }

  // Pagination methods
  get paginatedEmployees(): any[] {
    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    const endIndex = startIndex + this.itemsPerPage;
    return this.filteredEmployees.slice(startIndex, endIndex);
  }

  get totalPages(): number {
    return Math.ceil(this.totalItems / this.itemsPerPage);
  }

  previousPage(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
    }
  }

  nextPage(): void {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
    }
  }

  goToPage(page: number): void {
    if (page >= 1 && page <= this.totalPages) {
      this.currentPage = page;
    }
  }
}
