import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { NotificationService } from '../../services/notification.service';

interface Role {
  id: number;
  name: string;
  description: string;
  permissions: string[];
  userCount: number;
  createdDate: string;
  status: string;
}

@Component({
  selector: 'app-roles',
  standalone: true,
  imports: [CommonModule, FormsModule, ReactiveFormsModule],
  templateUrl: './roles.component.html',
  styleUrl: './roles.component.scss'
})
export class RolesComponent implements OnInit {
  private fb = inject(FormBuilder);
  private notificationService = inject(NotificationService);

  // Data properties
  roles: Role[] = [];
  availablePermissions: string[] = [];
  selectedPermissions: string[] = []; // Track selected permissions

  // UI state
  showAddModal = false;
  showRoleModal = false; // For the role modal
  isEditing = false;
  isLoading = false;
  selectedRole: Role | null = null;

  // Form
  roleForm!: FormGroup;

  constructor() {
    this.initializeForm();
  }

  ngOnInit(): void {
    this.loadMockData();
  }

  private initializeForm(): void {
    this.roleForm = this.fb.group({
      name: ['', [Validators.required, Validators.minLength(3)]],
      description: ['', [Validators.required, Validators.minLength(10)]],
      permissions: [[], Validators.required],
      status: ['Active', Validators.required]
    });
  }

  private loadMockData(): void {
    // Available permissions
    this.availablePermissions = [
      'dashboard.read',
      'dashboard.write',
      'employees.read',
      'employees.write',
      'employees.delete',
      'users.read',
      'users.write',
      'users.delete',
      'roles.read',
      'roles.write',
      'roles.delete',
      'reports.read',
      'reports.write',
      'reports.export',
      'settings.read',
      'settings.write',
      'audit.read',
      'system.admin'
    ];

    // Mock roles data
    this.roles = [
      {
        id: 1,
        name: 'Super Admin',
        description: 'Full system access with all permissions',
        permissions: ['system.admin', 'dashboard.read', 'dashboard.write', 'employees.read', 'employees.write', 'employees.delete', 'users.read', 'users.write', 'users.delete', 'roles.read', 'roles.write', 'roles.delete', 'reports.read', 'reports.write', 'reports.export', 'settings.read', 'settings.write', 'audit.read'],
        userCount: 1,
        createdDate: '2024-01-01',
        status: 'Active'
      },
      {
        id: 2,
        name: 'Manager',
        description: 'Department management and employee oversight',
        permissions: ['dashboard.read', 'employees.read', 'employees.write', 'reports.read', 'reports.export'],
        userCount: 5,
        createdDate: '2024-01-15',
        status: 'Active'
      },
      {
        id: 3,
        name: 'HR Admin',
        description: 'Human resources administration',
        permissions: ['dashboard.read', 'employees.read', 'employees.write', 'users.read', 'reports.read'],
        userCount: 3,
        createdDate: '2024-02-01',
        status: 'Active'
      },
      {
        id: 4,
        name: 'Employee',
        description: 'Basic employee access to view personal information',
        permissions: ['dashboard.read'],
        userCount: 25,
        createdDate: '2024-01-10',
        status: 'Active'
      }
    ];
  }

  // Modal methods
  addRole(): void {
    this.selectedRole = null;
    this.isEditing = false;
    this.selectedPermissions = [];
    this.showRoleModal = true;
    this.roleForm.reset();
    this.roleForm.patchValue({ status: 'Active' });
  }

  editRole(role: Role): void {
    this.selectedRole = { ...role };
    this.isEditing = true;
    this.selectedPermissions = [...role.permissions];
    this.showRoleModal = true;
    this.roleForm.patchValue({
      name: role.name,
      description: role.description,
      status: role.status
    });
  }

  closeModal(): void {
    this.showAddModal = false;
    this.showRoleModal = false;
    this.selectedRole = null;
    this.isEditing = false;
    this.selectedPermissions = [];
    this.roleForm.reset();
  }

  // Permission management methods
  toggleCategoryPermissions(category: string, event: any): void {
    const permissions = this.getPermissionsByCategory()[category] || [];

    if (event.target.checked) {
      // Add all category permissions
      permissions.forEach(permission => {
        if (!this.selectedPermissions.includes(permission)) {
          this.selectedPermissions.push(permission);
        }
      });
    } else {
      // Remove all category permissions
      permissions.forEach(permission => {
        const index = this.selectedPermissions.indexOf(permission);
        if (index > -1) {
          this.selectedPermissions.splice(index, 1);
        }
      });
    }
  }

  isCategorySelected(category: string): boolean {
    const categoryPermissions = this.getPermissionsByCategory()[category] || [];
    return categoryPermissions.length > 0 &&
           categoryPermissions.every(permission => this.selectedPermissions.includes(permission));
  }

  isCategoryIndeterminate(category: string): boolean {
    const categoryPermissions = this.getPermissionsByCategory()[category] || [];
    const selectedCount = categoryPermissions.filter(permission =>
      this.selectedPermissions.includes(permission)
    ).length;

    return selectedCount > 0 && selectedCount < categoryPermissions.length;
  }

  onPermissionChange(permission: string, event: any): void {
    if (event.target.checked) {
      if (!this.selectedPermissions.includes(permission)) {
        this.selectedPermissions.push(permission);
      }
    } else {
      const index = this.selectedPermissions.indexOf(permission);
      if (index > -1) {
        this.selectedPermissions.splice(index, 1);
      }
    }
  }

  isPermissionSelected(permission: string): boolean {
    return this.selectedPermissions.includes(permission);
  }

  getPermissionLabel(permission: string): string {
    return this.getPermissionDisplayName(permission);
  }

  removePermission(permission: string): void {
    const index = this.selectedPermissions.indexOf(permission);
    if (index > -1) {
      this.selectedPermissions.splice(index, 1);
    }
  }

  selectAllPermissions(): void {
    this.selectedPermissions = [...this.availablePermissions];
  }

  clearAllPermissions(): void {
    this.selectedPermissions = [];
  }

  // CRUD operations
  saveRole(): void {
    if (this.roleForm.valid) {
      this.isLoading = true;

      // Simulate API call
      setTimeout(() => {
        try {
          const formValue = this.roleForm.value;

          if (this.isEditing && this.selectedRole) {
            // Update existing role
            const roleIndex = this.roles.findIndex(r => r.id === this.selectedRole!.id);
            if (roleIndex !== -1) {
              this.roles[roleIndex] = {
                ...this.roles[roleIndex],
                name: formValue.name,
                description: formValue.description,
                permissions: [...this.selectedPermissions],
                status: formValue.status
              };

              this.notificationService.showSuccess('Success', 'Role updated successfully');
            }
          } else {
            // Create new role
            const newRole: Role = {
              id: Math.max(...this.roles.map(r => r.id)) + 1,
              name: formValue.name,
              description: formValue.description,
              permissions: [...this.selectedPermissions],
              userCount: 0,
              createdDate: new Date().toISOString().split('T')[0],
              status: formValue.status
            };

            this.roles.push(newRole);
            this.notificationService.showSuccess('Success', 'Role created successfully');
          }

          this.closeModal();
        } catch (error) {
          this.notificationService.showError('Error', 'Failed to save role');
        } finally {
          this.isLoading = false;
        }
      }, 1000);
    } else {
      this.notificationService.showWarning('Warning', 'Please fill in all required fields');
    }
  }

  deleteRole(role: Role): void {
    if (role.userCount > 0) {
      this.notificationService.showError('Error', `Cannot delete role "${role.name}" because it has ${role.userCount} users assigned to it.`);
      return;
    }

    if (confirm(`Are you sure you want to delete the role "${role.name}"? This action cannot be undone.`)) {
      const index = this.roles.findIndex(r => r.id === role.id);
      if (index !== -1) {
        this.roles.splice(index, 1);
        this.notificationService.showSuccess('Success', 'Role deleted successfully');
      }
    }
  }

  // Utility methods
  getRoleStatusClass(status: string): string {
    const statusClasses: { [key: string]: string } = {
      'Active': 'status-active',
      'Inactive': 'status-inactive'
    };
    return statusClasses[status] || 'status-default';
  }

  getPermissionDisplayName(permission: string): string {
    const displayNames: { [key: string]: string } = {
      'dashboard.read': 'View Dashboard',
      'dashboard.write': 'Edit Dashboard',
      'employees.read': 'View Employees',
      'employees.write': 'Edit Employees',
      'employees.delete': 'Delete Employees',
      'users.read': 'View Users',
      'users.write': 'Edit Users',
      'users.delete': 'Delete Users',
      'roles.read': 'View Roles',
      'roles.write': 'Edit Roles',
      'roles.delete': 'Delete Roles',
      'reports.read': 'View Reports',
      'reports.write': 'Create Reports',
      'reports.export': 'Export Reports',
      'settings.read': 'View Settings',
      'settings.write': 'Edit Settings',
      'audit.read': 'View Audit Logs',
      'system.admin': 'System Administration'
    };
    return displayNames[permission] || permission;
  }

  getPermissionCategory(permission: string): string {
    const parts = permission.split('.');
    return parts[0].charAt(0).toUpperCase() + parts[0].slice(1);
  }

  getPermissionsByCategory(): { [key: string]: string[] } {
    const categories: { [key: string]: string[] } = {};

    this.availablePermissions.forEach(permission => {
      const category = this.getPermissionCategory(permission);
      if (!categories[category]) {
        categories[category] = [];
      }
      categories[category].push(permission);
    });

    return categories;
  }

  formatDate(dateString: string): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  }

  getStatusClass(status: string): string {
    return this.getRoleStatusClass(status);
  }

  // Form validation helper
  isFieldInvalid(fieldName: string): boolean {
    const field = this.roleForm.get(fieldName);
    return !!(field && field.invalid && (field.dirty || field.touched));
  }
}
