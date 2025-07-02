import { Routes } from '@angular/router';
import { AuthGuard } from './guards/auth.guard';
import { LoginGuard } from './guards/login.guard';

export const routes: Routes = [
  // Public routes (authentication)
  {
    path: 'login',
    loadComponent: () => import('./components/login/login.component').then(m => m.LoginComponent),
    canActivate: [LoginGuard]
  },
  {
    path: 'two-factor',
    loadComponent: () => import('./components/two-factor/two-factor.component').then(m => m.TwoFactorComponent)
  },

  // Protected routes (require authentication)
  {
    path: '',
    redirectTo: '/dashboard',
    pathMatch: 'full'
  },
  {
    path: 'dashboard',
    loadComponent: () => import('./components/dashboard/dashboard.component').then(m => m.DashboardComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'employees',
    loadComponent: () => import('./components/employees/employees.component').then(m => m.EmployeesComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'users',
    loadComponent: () => import('./components/users/users.component').then(m => m.UsersComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'roles',
    loadComponent: () => import('./components/roles/roles.component').then(m => m.RolesComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'reports',
    loadComponent: () => import('./components/reports/reports.component').then(m => m.ReportsComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'settings',
    loadComponent: () => import('./components/settings/settings.component').then(m => m.SettingsComponent),
    canActivate: [AuthGuard]
  },

  // Fallback route
  {
    path: '**',
    redirectTo: '/login'
  }
];
