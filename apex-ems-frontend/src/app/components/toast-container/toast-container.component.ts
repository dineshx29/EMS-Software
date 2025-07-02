import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NotificationService, ToastNotification } from '../../services/notification.service';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-toast-container',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="toast-container" [class]="'position-' + position" *ngFor="let position of positions">
      <div 
        *ngFor="let toast of getToastsForPosition(position); trackBy: trackByToastId" 
        class="toast-item"
        [class.success]="toast.type === 'success'"
        [class.warning]="toast.type === 'warning'"
        [class.error]="toast.type === 'error'"
        [class.info]="toast.type === 'info'"
        [class.show]="true"
      >
        <div class="toast-content">
          <div class="toast-icon">
            <i class="pi" [ngClass]="getToastIcon(toast.type)"></i>
          </div>
          
          <div class="toast-message">
            <div class="toast-title">{{ toast.title }}</div>
            <div class="toast-text" *ngIf="toast.message">{{ toast.message }}</div>
          </div>

          <div class="toast-actions">
            <button 
              class="toast-action-btn" 
              *ngIf="toast.action"
              (click)="executeAction(toast)"
            >
              {{ toast.action.label }}
            </button>
            <button 
              class="toast-close-btn" 
              (click)="dismissToast(toast.id)"
              title="Close"
            >
              <i class="pi pi-times"></i>
            </button>
          </div>
        </div>

        <!-- Progress bar for auto-dismiss -->
        <div 
          class="toast-progress" 
          *ngIf="toast.showProgress && toast.duration && toast.duration > 0"
          [style.animation-duration.ms]="toast.duration"
        ></div>
      </div>
    </div>
  `,
  styleUrls: ['./toast-container.component.scss']
})
export class ToastContainerComponent implements OnInit, OnDestroy {
  toasts: ToastNotification[] = [];
  positions = ['top-right', 'top-left', 'bottom-right', 'bottom-left'];

  private subscription!: Subscription;

  constructor(private notificationService: NotificationService) {}

  ngOnInit(): void {
    this.subscription = this.notificationService.getToasts().subscribe(
      toasts => {
        this.toasts = toasts;
      }
    );
  }

  ngOnDestroy(): void {
    if (this.subscription) {
      this.subscription.unsubscribe();
    }
  }

  getToastsForPosition(position: string): ToastNotification[] {
    return this.toasts.filter(toast => toast.position === position);
  }

  dismissToast(id: string): void {
    this.notificationService.dismissToast(id);
  }

  executeAction(toast: ToastNotification): void {
    if (toast.action) {
      toast.action.callback();
      this.dismissToast(toast.id);
    }
  }

  getToastIcon(type: string): string {
    switch (type) {
      case 'success': return 'pi-check-circle';
      case 'warning': return 'pi-exclamation-triangle';
      case 'error': return 'pi-times-circle';
      case 'info': return 'pi-info-circle';
      default: return 'pi-bell';
    }
  }

  trackByToastId(index: number, toast: ToastNotification): string {
    return toast.id;
  }
}
