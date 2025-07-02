import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { NotificationService } from '../../services/notification.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss']
})
export class LoginComponent implements OnInit {
  loginData = {
    username: '',
    password: ''
  };

  showPassword = false;
  isLoading = false;

  constructor(
    private authService: AuthService,
    private notificationService: NotificationService,
    private router: Router
  ) {}

  ngOnInit(): void {
    // Check if already authenticated
    if (this.authService.isAuthenticated()) {
      this.router.navigate(['/dashboard']);
      return;
    }
  }

  async onLogin(): Promise<void> {
    if (!this.loginData.username || !this.loginData.password) {
      this.notificationService.showWarning(
        'Validation Error',
        'Please enter both username and password'
      );
      return;
    }

    this.isLoading = true;

    try {
      const result = await this.authService.login(
        this.loginData.username,
        this.loginData.password
      );

      if (result.status === 'success' && result.data) {
        this.notificationService.showSuccess(
          'Login Successful',
          `Welcome back, ${result.data.user.full_name}!`
        );
        // Navigation is handled by the AuthService
      } else {
        this.notificationService.showError(
          'Login Failed',
          result.message || 'Invalid credentials. Please check your username and password.'
        );
      }
    } catch (error) {
      console.error('Login error:', error);
      this.notificationService.showError(
        'Login Error',
        'An unexpected error occurred. Please try again.'
      );
    } finally {
      this.isLoading = false;
    }
  }

  useDemoCredentials(): void {
    this.loginData.username = 'admin';
    this.loginData.password = 'admin123';
    this.notificationService.showInfo(
      'Demo Credentials',
      'Demo credentials have been filled in. Click Sign In to continue.'
    );
  }
}
