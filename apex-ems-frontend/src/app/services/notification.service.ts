import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';

export interface Notification {
  id: string;
  type: 'success' | 'warning' | 'error' | 'info';
  title: string;
  message: string;
  timestamp: Date;
  read: boolean;
  persistent?: boolean;
  action?: {
    label: string;
    callback: () => void;
  };
}

export interface ToastNotification extends Notification {
  duration?: number;
  showProgress?: boolean;
  position?: 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left';
}

@Injectable({
  providedIn: 'root'
})
export class NotificationService {
  private notifications$ = new BehaviorSubject<Notification[]>([]);
  private toasts$ = new BehaviorSubject<ToastNotification[]>([]);
  private storageKey = 'apex-ems-notifications';

  constructor() {
    this.loadNotifications();
  }

  // Notification Center Methods
  getNotifications(): Observable<Notification[]> {
    return this.notifications$.asObservable();
  }

  getUnreadCount(): Observable<number> {
    return new Observable(observer => {
      this.notifications$.subscribe(notifications => {
        const unreadCount = notifications.filter(n => !n.read).length;
        observer.next(unreadCount);
      });
    });
  }

  addNotification(notification: Omit<Notification, 'id' | 'timestamp' | 'read'>): void {
    const newNotification: Notification = {
      ...notification,
      id: this.generateId(),
      timestamp: new Date(),
      read: false
    };

    const currentNotifications = this.notifications$.value;
    const updatedNotifications = [newNotification, ...currentNotifications];

    this.notifications$.next(updatedNotifications);
    this.saveNotifications();
  }

  markAsRead(id: string): void {
    const notifications = this.notifications$.value.map(n =>
      n.id === id ? { ...n, read: true } : n
    );
    this.notifications$.next(notifications);
    this.saveNotifications();
  }

  markAsUnread(id: string): void {
    const notifications = this.notifications$.value.map(n =>
      n.id === id ? { ...n, read: false } : n
    );
    this.notifications$.next(notifications);
    this.saveNotifications();
  }

  deleteNotification(id: string): void {
    const notifications = this.notifications$.value.filter(n => n.id !== id);
    this.notifications$.next(notifications);
    this.saveNotifications();
  }

  clearAllNotifications(): void {
    this.notifications$.next([]);
    this.saveNotifications();
  }

  markAllAsRead(): void {
    const notifications = this.notifications$.value.map(n => ({ ...n, read: true }));
    this.notifications$.next(notifications);
    this.saveNotifications();
  }

  // Toast Methods
  getToasts(): Observable<ToastNotification[]> {
    return this.toasts$.asObservable();
  }

  showToast(toast: Omit<ToastNotification, 'id' | 'timestamp' | 'read'>): void {
    const newToast: ToastNotification = {
      ...toast,
      id: this.generateId(),
      timestamp: new Date(),
      read: false,
      duration: toast.duration || 5000,
      showProgress: toast.showProgress !== false,
      position: toast.position || 'top-right'
    };

    // Add to notification center if persistent
    if (toast.persistent !== false) {
      this.addNotification(newToast);
    }

    // Add to toast queue
    const currentToasts = this.toasts$.value;
    const updatedToasts = [...currentToasts, newToast];
    this.toasts$.next(updatedToasts);

    // Auto-dismiss toast
    if (newToast.duration && newToast.duration > 0) {
      setTimeout(() => {
        this.dismissToast(newToast.id);
      }, newToast.duration);
    }
  }

  dismissToast(id: string): void {
    const toasts = this.toasts$.value.filter(t => t.id !== id);
    this.toasts$.next(toasts);
  }

  // Convenience methods for different toast types
  showSuccess(title: string, message: string, options?: Partial<ToastNotification>): void {
    this.showToast({
      type: 'success',
      title,
      message,
      ...options
    });
  }

  showError(title: string, message: string, options?: Partial<ToastNotification>): void {
    this.showToast({
      type: 'error',
      title,
      message,
      duration: 0, // Don't auto-dismiss errors
      ...options
    });
  }

  showWarning(title: string, message: string, options?: Partial<ToastNotification>): void {
    this.showToast({
      type: 'warning',
      title,
      message,
      ...options
    });
  }

  showInfo(title: string, message: string, options?: Partial<ToastNotification>): void {
    this.showToast({
      type: 'info',
      title,
      message,
      ...options
    });
  }

  // Private methods
  private generateId(): string {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
  }

  private saveNotifications(): void {
    try {
      if (typeof window !== 'undefined' && window.localStorage) {
        const notifications = this.notifications$.value;
        localStorage.setItem(this.storageKey, JSON.stringify(notifications));
      }
    } catch (error) {
      console.warn('Failed to save notifications to localStorage:', error);
    }
  }

  private loadNotifications(): void {
    try {
      if (typeof window !== 'undefined' && window.localStorage) {
        const stored = localStorage.getItem(this.storageKey);
        if (stored) {
          const notifications = JSON.parse(stored).map((n: any) => ({
            ...n,
            timestamp: new Date(n.timestamp)
          }));
          this.notifications$.next(notifications);
        }
      }
    } catch (error) {
      console.warn('Failed to load notifications from localStorage:', error);
    }
  }
}
