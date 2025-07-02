import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { NotificationService } from '../../services/notification.service';

@Component({
  selector: 'app-two-factor',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './two-factor.component.html',
  styleUrls: ['./two-factor.component.scss']
})
export class TwoFactorComponent implements OnInit {
  twoFactorForm: FormGroup;
  isLoading = false;
  countdown = 60;
  canResend = false;
  countdownInterval: any;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private notificationService: NotificationService
  ) {
    this.twoFactorForm = this.fb.group({
      code: ['', [Validators.required, Validators.pattern(/^\d{6}$/)]]
    });
  }

  ngOnInit(): void {
    this.startCountdown();

    // Check if user has temporary session
    if (typeof window !== 'undefined' && window.sessionStorage) {
      const tempUser = sessionStorage.getItem('temp-user');
      if (!tempUser) {
        this.router.navigate(['/login']);
        return;
      }
    } else {
      // If no sessionStorage (SSR), redirect to login
      this.router.navigate(['/login']);
      return;
    }
  }

  ngOnDestroy(): void {
    if (this.countdownInterval) {
      clearInterval(this.countdownInterval);
    }
  }

  async onSubmit(): Promise<void> {
    if (this.twoFactorForm.valid && !this.isLoading) {
      this.isLoading = true;

      try {
        const code = this.twoFactorForm.get('code')?.value;
        const result = await this.authService.verifyTwoFactor(code);

        if (result.success) {
          this.notificationService.showSuccess(
            'Authentication Successful',
            result.message || 'Two-factor authentication completed'
          );
          this.router.navigate(['/dashboard']);
        } else {
          this.notificationService.showError(
            'Verification Failed',
            result.message || 'Invalid verification code'
          );
          this.twoFactorForm.get('code')?.setErrors({ invalid: true });
        }
      } catch (error) {
        this.notificationService.showError(
          'Error',
          'An unexpected error occurred. Please try again.'
        );
      } finally {
        this.isLoading = false;
      }
    }
  }

  onCodeInput(event: any): void {
    const value = event.target.value.replace(/\D/g, '').slice(0, 6);
    this.twoFactorForm.get('code')?.setValue(value);

    // Auto-submit when 6 digits are entered
    if (value.length === 6) {
      setTimeout(() => this.onSubmit(), 500);
    }
  }

  onResendCode(): void {
    if (this.canResend) {
      this.canResend = false;
      this.countdown = 60;
      this.startCountdown();

      this.notificationService.showInfo(
        'Code Sent',
        'A new verification code has been sent to your registered device'
      );
    }
  }

  goBackToLogin(): void {
    if (typeof window !== 'undefined' && window.sessionStorage) {
      sessionStorage.removeItem('temp-user');
    }
    this.router.navigate(['/login']);
  }

  private startCountdown(): void {
    this.countdownInterval = setInterval(() => {
      this.countdown--;
      if (this.countdown <= 0) {
        this.canResend = true;
        clearInterval(this.countdownInterval);
      }
    }, 1000);
  }

  // Helper method to get demo codes for display
  getDemoCodes(): string[] {
    // Since two-factor is not implemented yet, return empty array
    return [];
  }
}
