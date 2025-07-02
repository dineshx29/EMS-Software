import { Component, OnInit, OnDestroy, ElementRef, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NotificationService, Notification } from '../../services/notification.service';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-notification-center',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="notification-center">
      <!-- Notification Bell Icon -->
      <button 
        class="notification-bell" 
        (click)="toggleDropdown()"
        [class.has-notifications]="unreadCount > 0"
      >
        <i class="pi pi-bell"></i>
        <span class="notification-badge" *ngIf="unreadCount > 0">{{ unreadCount }}</span>
      </button>

      <!-- Notification Dropdown -->
      <div 
        class="notification-dropdown" 
        [class.show]="isDropdownOpen"
        #dropdown
      >
        <div class="notification-header">
          <h3>Notifications</h3>
          <div class="notification-actions">
            <button 
              class="action-btn" 
              (click)="markAllAsRead()" 
              *ngIf="unreadCount > 0"
              title="Mark all as read"
            >
              <i class="pi pi-check-circle"></i>
            </button>
            <button 
              class="action-btn" 
              (click)="clearAll()" 
              *ngIf="notifications.length > 0"
              title="Clear all"
            >
              <i class="pi pi-trash"></i>
            </button>
          </div>
        </div>

        <div class="notification-list" *ngIf="notifications.length > 0; else noNotifications">
          <div 
            *ngFor="let notification of notifications; trackBy: trackByNotificationId" 
            class="notification-item"
            [class.unread]="!notification.read"
            [class.success]="notification.type === 'success'"
            [class.warning]="notification.type === 'warning'"
            [class.error]="notification.type === 'error'"
            [class.info]="notification.type === 'info'"
          >
            <div class="notification-icon">
              <i class="pi" [ngClass]="getNotificationIcon(notification.type)"></i>
            </div>
            
            <div class="notification-content">
              <div class="notification-title">{{ notification.title }}</div>
              <div class="notification-message">{{ notification.message }}</div>
              <div class="notification-time">{{ getRelativeTime(notification.timestamp) }}</div>
            </div>

            <div class="notification-controls">
              <button 
                class="control-btn" 
                (click)="toggleReadStatus(notification)"
                [title]="notification.read ? 'Mark as unread' : 'Mark as read'"
              >
                <i class="pi" [ngClass]="notification.read ? 'pi-circle' : 'pi-check-circle'"></i>
              </button>
              <button 
                class="control-btn delete-btn" 
                (click)="deleteNotification(notification.id)"
                title="Delete notification"
              >
                <i class="pi pi-times"></i>
              </button>
            </div>
          </div>
        </div>

        <ng-template #noNotifications>
          <div class="no-notifications">
            <i class="pi pi-bell-slash"></i>
            <p>No notifications</p>
          </div>
        </ng-template>
      </div>
    </div>

    <!-- Backdrop for closing dropdown -->
    <div 
      class="notification-backdrop" 
      *ngIf="isDropdownOpen" 
      (click)="closeDropdown()"
    ></div>
  `,
  styleUrls: ['./notification-center.component.scss']
})
export class NotificationCenterComponent implements OnInit, OnDestroy {
  @ViewChild('dropdown') dropdown!: ElementRef;

  notifications: Notification[] = [];
  unreadCount = 0;
  isDropdownOpen = false;

  private subscriptions: Subscription[] = [];

  constructor(private notificationService: NotificationService) {}

  ngOnInit(): void {
    // Subscribe to notifications
    const notificationSub = this.notificationService.getNotifications().subscribe(
      notifications => {
        this.notifications = notifications;
      }
    );

    // Subscribe to unread count
    const unreadSub = this.notificationService.getUnreadCount().subscribe(
      count => {
        this.unreadCount = count;
      }
    );

    this.subscriptions.push(notificationSub, unreadSub);

    // Close dropdown when clicking outside
    document.addEventListener('click', this.handleOutsideClick.bind(this));
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach(sub => sub.unsubscribe());
    document.removeEventListener('click', this.handleOutsideClick.bind(this));
  }

  toggleDropdown(): void {
    this.isDropdownOpen = !this.isDropdownOpen;
  }

  closeDropdown(): void {
    this.isDropdownOpen = false;
  }

  toggleReadStatus(notification: Notification): void {
    if (notification.read) {
      this.notificationService.markAsUnread(notification.id);
    } else {
      this.notificationService.markAsRead(notification.id);
    }
  }

  deleteNotification(id: string): void {
    this.notificationService.deleteNotification(id);
  }

  markAllAsRead(): void {
    this.notificationService.markAllAsRead();
  }

  clearAll(): void {
    if (confirm('Are you sure you want to clear all notifications?')) {
      this.notificationService.clearAllNotifications();
    }
  }

  getNotificationIcon(type: string): string {
    switch (type) {
      case 'success': return 'pi-check-circle';
      case 'warning': return 'pi-exclamation-triangle';
      case 'error': return 'pi-times-circle';
      case 'info': return 'pi-info-circle';
      default: return 'pi-bell';
    }
  }

  getRelativeTime(timestamp: Date): string {
    const now = new Date();
    const diff = now.getTime() - timestamp.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days === 1) return 'Yesterday';
    if (days < 7) return `${days}d ago`;
    
    return timestamp.toLocaleDateString();
  }

  trackByNotificationId(index: number, notification: Notification): string {
    return notification.id;
  }

  private handleOutsideClick(event: Event): void {
    if (this.dropdown && !this.dropdown.nativeElement.contains(event.target as Node)) {
      const target = event.target as HTMLElement;
      if (!target.closest('.notification-bell')) {
        this.closeDropdown();
      }
    }
  }
}
