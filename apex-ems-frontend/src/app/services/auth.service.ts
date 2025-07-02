import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, of, firstValueFrom } from 'rxjs';
import { Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { map, catchError } from 'rxjs/operators';
import { NotificationService } from './notification.service';

export interface User {
  id: string;
  username: string;
  email: string;
  full_name: string;
  avatar?: string | null;
  status: string;
  roles: Array<{
    id: string;
    name: string;
    description: string;
    created_at: string;
    user_id: string;
  }>;
  permissions: Array<{
    id: string;
    name: string;
    group_name: string;
    role_id: string;
  }>;
}

export interface LoginResponse {
  status: string;
  message: string;
  data?: {
    token: string;
    user: User;
  };
}

export interface TwoFactorResponse {
  success: boolean;
  user?: User;
  message?: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = 'http://localhost/Projects/Project/backend/ems-api/public/api';
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  private isAuthenticatedSubject = new BehaviorSubject<boolean>(false);
  private storageKey = 'apex-ems-user';
  private tokenKey = 'apex-ems-token';

  currentUser$ = this.currentUserSubject.asObservable();
  isAuthenticated$ = this.isAuthenticatedSubject.asObservable();

  constructor(
    private router: Router,
    private http: HttpClient,
    private notificationService: NotificationService
  ) {
    this.checkStoredAuth();
  }
  async login(username: string, password: string): Promise<LoginResponse> {
    try {
      const loginData = { username, password };

      const response = await firstValueFrom(
        this.http.post<LoginResponse>(`${this.apiUrl}/auth/login`, loginData)
      );

      if (response && response.status === 'success' && response.data) {
        // Store the JWT token
        if (typeof window !== 'undefined' && window.localStorage) {
          localStorage.setItem(this.tokenKey, response.data.token);
        }

        // Set the current user
        this.setCurrentUser(response.data.user);

        return response;
      } else {
        return {
          status: 'error',
          message: response?.message || 'Login failed'
        };
      }
    } catch (error: any) {
      console.error('Login error:', error);

      // Handle API error response
      if (error?.error?.messages?.error) {
        return {
          status: 'error',
          message: error.error.messages.error
        };
      }

      // Handle network/CORS errors
      if (error?.status === 0) {
        return {
          status: 'error',
          message: 'Unable to connect to the server. Please check if the backend is running.'
        };
      }

      return {
        status: 'error',
        message: 'Login failed. Please check your credentials and try again.'
      };
    }
  }

  async verifyTwoFactor(code: string): Promise<TwoFactorResponse> {
    // Two-factor authentication is not implemented in the backend yet
    // This is a placeholder for future implementation
    return {
      success: false,
      message: 'Two-factor authentication not implemented yet'
    };
  }

  logout(): void {
    if (typeof window !== 'undefined' && window.localStorage) {
      localStorage.removeItem(this.storageKey);
      localStorage.removeItem(this.tokenKey);
    }
    if (typeof window !== 'undefined' && window.sessionStorage) {
      sessionStorage.removeItem('temp-user');
    }
    this.currentUserSubject.next(null);
    this.isAuthenticatedSubject.next(false);
    this.router.navigate(['/login']);

    this.notificationService.showInfo(
      'Logged Out',
      'You have been successfully logged out'
    );
  }

  getCurrentUser(): User | null {
    return this.currentUserSubject.value;
  }

  isAuthenticated(): boolean {
    return this.isAuthenticatedSubject.value;
  }

  getToken(): string | null {
    if (typeof window !== 'undefined' && window.localStorage) {
      return localStorage.getItem(this.tokenKey);
    }
    return null;
  }

  private setCurrentUser(user: User): void {
    if (typeof window !== 'undefined' && window.localStorage) {
      localStorage.setItem(this.storageKey, JSON.stringify(user));
    }
    this.currentUserSubject.next(user);
    this.isAuthenticatedSubject.next(true);

    // Check for redirect URL
    if (typeof window !== 'undefined' && window.sessionStorage) {
      const redirectUrl = sessionStorage.getItem('redirectUrl');
      if (redirectUrl) {
        sessionStorage.removeItem('redirectUrl');
        this.router.navigate([redirectUrl]);
        return;
      }
    }
    this.router.navigate(['/dashboard']);
  }

  private checkStoredAuth(): void {
    try {
      if (typeof window !== 'undefined' && window.localStorage) {
        const stored = localStorage.getItem(this.storageKey);
        const token = localStorage.getItem(this.tokenKey);

        if (stored && token) {
          const user: User = JSON.parse(stored);
          this.currentUserSubject.next(user);
          this.isAuthenticatedSubject.next(true);
        }
      }
    } catch (error) {
      console.warn('Failed to load stored authentication:', error);
      if (typeof window !== 'undefined' && window.localStorage) {
        localStorage.removeItem(this.storageKey);
        localStorage.removeItem(this.tokenKey);
      }
    }
  }

  // Utility method to check if user has specific permission
  hasPermission(permission: string): boolean {
    const user = this.getCurrentUser();
    if (!user || !user.permissions) {
      return false;
    }
    return user.permissions.some(p => p.name === permission);
  }

  // Utility method to check if user has specific role
  hasRole(roleName: string): boolean {
    const user = this.getCurrentUser();
    if (!user || !user.roles) {
      return false;
    }
    return user.roles.some(r => r.name === roleName);
  }

  // Get available credentials for demo/testing
  getDemoCredentials() {
    return [
      { username: 'admin', password: 'admin123', role: 'Super Admin' },
      { username: 'hrmanager', password: 'hr123', role: 'HR Manager' },
      { username: 'deptmanager', password: 'dept123', role: 'Department Manager' }
    ];
  }
}
