import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-roles',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './roles.component.html',
  styleUrl: './roles.component.scss'
})
export class RolesComponent implements OnInit {

  roles = [
    {
      id: 1,
      name: 'Super Admin',
      description: 'Full system access with all permissions',
      permissions: ['all'],
      userCount: 1,
      createdDate: '2024-01-01',
      status: 'Active'
    },
    {
      id: 2,
      name: 'Manager',
      description: 'Department management and employee oversight',
      permissions: ['employees.read', 'employees.write', 'reports.read'],
      userCount: 5,
      createdDate: '2024-01-15',
      status: 'Active'
    },
    {
      id: 3,
      name: 'HR Admin',
      description: 'Human resources administration',
      permissions: ['employees.read', 'employees.write', 'users.read'],
      userCount: 3,
      createdDate: '2024-02-01',
      status: 'Active'
    }
  ];

  constructor() { }

  ngOnInit(): void {
  }

  addRole(): void {
    alert('Add Role functionality will be implemented');
  }

  editRole(role: any): void {
    alert(`Edit Role: ${role.name}`);
  }

  deleteRole(role: any): void {
    if (confirm(`Delete role ${role.name}?`)) {
      alert('Role deleted (demo)');
    }
  }
}
