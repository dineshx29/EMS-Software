import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-users',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './users.component.html',
  styleUrl: './users.component.scss'
})
export class UsersComponent implements OnInit {
  
  users = [
    {
      id: 1,
      username: 'admin',
      email: 'admin@apexems.com',
      firstName: 'System',
      lastName: 'Administrator',
      role: 'Super Admin',
      status: 'Active',
      lastLogin: '2024-07-01T08:30:00',
      createdDate: '2024-01-01T00:00:00'
    },
    {
      id: 2,
      username: 'jsmith',
      email: 'john.smith@company.com',
      firstName: 'John',
      lastName: 'Smith',
      role: 'Manager',
      status: 'Active',
      lastLogin: '2024-06-30T16:45:00',
      createdDate: '2024-02-15T00:00:00'
    },
    {
      id: 3,
      username: 'sjohnson',
      email: 'sarah.johnson@company.com',
      firstName: 'Sarah',
      lastName: 'Johnson',
      role: 'HR Admin',
      status: 'Active',
      lastLogin: '2024-06-30T14:20:00',
      createdDate: '2024-03-10T00:00:00'
    }
  ];

  filteredUsers = [...this.users];
  searchTerm = '';
  selectedRole = '';
  selectedStatus = '';

  roles = ['Super Admin', 'Manager', 'HR Admin', 'Employee', 'Viewer'];
  statuses = ['Active', 'Inactive', 'Suspended'];

  showModal = false;
  modalMode: 'add' | 'edit' | 'view' = 'add';
  selectedUser: any = {};

  constructor() { }

  ngOnInit(): void {
    this.applyFilters();
  }

  applyFilters(): void {
    this.filteredUsers = this.users.filter(user => {
      const matchesSearch = !this.searchTerm || 
        user.username.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        user.email.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        `${user.firstName} ${user.lastName}`.toLowerCase().includes(this.searchTerm.toLowerCase());

      const matchesRole = !this.selectedRole || user.role === this.selectedRole;
      const matchesStatus = !this.selectedStatus || user.status === this.selectedStatus;

      return matchesSearch && matchesRole && matchesStatus;
    });
  }

  onSearchChange(): void {
    this.applyFilters();
  }

  onRoleChange(): void {
    this.applyFilters();
  }

  onStatusChange(): void {
    this.applyFilters();
  }

  addUser(): void {
    this.modalMode = 'add';
    this.selectedUser = {
      username: '',
      email: '',
      firstName: '',
      lastName: '',
      role: 'Employee',
      status: 'Active',
      password: ''
    };
    this.showModal = true;
  }

  editUser(user: any): void {
    this.modalMode = 'edit';
    this.selectedUser = { ...user };
    this.showModal = true;
  }

  viewUser(user: any): void {
    this.modalMode = 'view';
    this.selectedUser = { ...user };
    this.showModal = true;
  }

  deleteUser(user: any): void {
    if (confirm(`Are you sure you want to delete user ${user.username}?`)) {
      const index = this.users.findIndex(u => u.id === user.id);
      if (index > -1) {
        this.users.splice(index, 1);
        this.applyFilters();
      }
    }
  }

  saveUser(): void {
    if (this.modalMode === 'add') {
      const newId = Math.max(...this.users.map(u => u.id)) + 1;
      this.selectedUser.id = newId;
      this.selectedUser.createdDate = new Date().toISOString();
      this.selectedUser.lastLogin = null;
      this.users.push({ ...this.selectedUser });
    } else if (this.modalMode === 'edit') {
      const index = this.users.findIndex(u => u.id === this.selectedUser.id);
      if (index > -1) {
        this.users[index] = { ...this.selectedUser };
      }
    }

    this.closeModal();
    this.applyFilters();
  }

  closeModal(): void {
    this.showModal = false;
    this.selectedUser = {};
  }

  getStatusClass(status: string): string {
    switch (status.toLowerCase()) {
      case 'active': return 'status-active';
      case 'inactive': return 'status-inactive';
      case 'suspended': return 'status-suspended';
      default: return 'status-default';
    }
  }

  getRoleClass(role: string): string {
    switch (role.toLowerCase()) {
      case 'super admin': return 'role-super-admin';
      case 'manager': return 'role-manager';
      case 'hr admin': return 'role-hr-admin';
      case 'employee': return 'role-employee';
      case 'viewer': return 'role-viewer';
      default: return 'role-default';
    }
  }

  formatDateTime(dateString: string): string {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleString('en-US');
  }

  resetPassword(user: any): void {
    if (confirm(`Reset password for ${user.username}?`)) {
      alert('Password reset functionality would be implemented here');
    }
  }
}
