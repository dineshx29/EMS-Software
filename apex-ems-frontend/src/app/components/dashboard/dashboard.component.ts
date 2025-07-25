import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { NotificationService } from '../../services/notification.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss'
})
export class DashboardComponent implements OnInit {

  constructor(private notificationService: NotificationService) {}

  // Dashboard statistics
  dashboardStats = [
    {
      title: 'Total Employees',
      value: 1247,
      icon: 'pi-users',
      color: 'primary',
      trend: '+12%',
      trendUp: true,
      description: 'vs last month'
    },
    {
      title: 'Active Users',
      value: 892,
      icon: 'pi-user',
      color: 'success',
      trend: '+8%',
      trendUp: true,
      description: 'logged in today'
    },
    {
      title: 'Pending Approvals',
      value: 23,
      icon: 'pi-clock',
      color: 'warning',
      trend: '-15%',
      trendUp: false,
      description: 'awaiting action'
    },
    {
      title: 'System Health',
      value: 98.7,
      icon: 'pi-shield',
      color: 'info',
      trend: '+0.3%',
      trendUp: true,
      description: 'uptime percentage'
    }
  ];

  // Recent activities
  recentActivities = [
    {
      user: 'John Smith',
      action: 'Created new employee record',
      time: '2 minutes ago',
      icon: 'pi-plus',
      type: 'create'
    },
    {
      user: 'Sarah Johnson',
      action: 'Updated role permissions',
      time: '15 minutes ago',
      icon: 'pi-pencil',
      type: 'update'
    },
    {
      user: 'Michael Brown',
      action: 'Generated monthly report',
      time: '1 hour ago',
      icon: 'pi-file-pdf',
      type: 'report'
    },
    {
      user: 'Emily Davis',
      action: 'Approved leave request',
      time: '2 hours ago',
      icon: 'pi-check',
      type: 'approval'
    },
    {
      user: 'System Admin',
      action: 'Database backup completed',
      time: '4 hours ago',
      icon: 'pi-database',
      type: 'system'
    }
  ];

  // Quick actions
  quickActions = [
    {
      title: 'Add Employee',
      description: 'Create a new employee record',
      icon: 'pi-user-plus',
      route: '/employees',
      color: 'primary'
    },
    {
      title: 'User Management',
      description: 'Manage user accounts and permissions',
      icon: 'pi-users',
      route: '/users',
      color: 'secondary'
    },
    {
      title: 'Generate Report',
      description: 'Create custom reports and analytics',
      icon: 'pi-chart-bar',
      route: '/reports',
      color: 'success'
    },
    {
      title: 'System Settings',
      description: 'Configure system parameters',
      icon: 'pi-cog',
      route: '/settings',
      color: 'info'
    }
  ];

  // Chart data for employee trends
  employeeTrendData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [
      {
        label: 'New Hires',
        data: [12, 19, 15, 25, 22, 30],
        borderColor: '#0066cc',
        backgroundColor: 'rgba(0, 102, 204, 0.1)',
        tension: 0.4
      },
      {
        label: 'Departures',
        data: [5, 8, 6, 10, 7, 12],
        borderColor: '#dc3545',
        backgroundColor: 'rgba(220, 53, 69, 0.1)',
        tension: 0.4
      }
    ]
  };

  ngOnInit(): void {
    // Initialize dashboard data
    this.loadDashboardData();

    // Add some demo notifications when dashboard loads
    this.addDemoNotifications();
  }

  loadDashboardData(): void {
    // Simulate loading dashboard data
    console.log('Loading dashboard data...');
  }

  addDemoNotifications(): void {
    // Add some sample notifications to demonstrate the notification system
    setTimeout(() => {
      this.notificationService.showSuccess(
        'Dashboard Loaded',
        'Welcome to the Employee Management System'
      );
    }, 1000);

    setTimeout(() => {
      this.notificationService.addNotification({
        type: 'info',
        title: 'System Update',
        message: 'A new version of the system is available. Please review the changelog.'
      });
    }, 2000);

    setTimeout(() => {
      this.notificationService.addNotification({
        type: 'warning',
        title: 'Pending Approvals',
        message: 'You have 23 employee requests awaiting approval.'
      });
    }, 3000);
  }

  testNotifications(): void {
    // Test different types of notifications
    this.notificationService.showSuccess(
      'Success!',
      'Operation completed successfully'
    );

    setTimeout(() => {
      this.notificationService.showWarning(
        'Warning',
        'Please check your input data'
      );
    }, 500);

    setTimeout(() => {
      this.notificationService.showError(
        'Error',
        'Failed to save changes. Please try again.'
      );
    }, 1000);

    setTimeout(() => {
      this.notificationService.showInfo(
        'Information',
        'New features are now available in the system',
        {
          action: {
            label: 'Learn More',
            callback: () => {
              this.notificationService.showSuccess('Action', 'You clicked the action button!');
            }
          }
        }
      );
    }, 1500);
  }

  getColorClass(color: string): string {
    switch (color) {
      case 'primary': return 'stat-primary';
      case 'success': return 'stat-success';
      case 'warning': return 'stat-warning';
      case 'info': return 'stat-info';
      case 'danger': return 'stat-danger';
      default: return 'stat-primary';
    }
  }

  getActivityTypeClass(type: string): string {
    switch (type) {
      case 'create': return 'activity-create';
      case 'update': return 'activity-update';
      case 'report': return 'activity-report';
      case 'approval': return 'activity-approval';
      case 'system': return 'activity-system';
      default: return 'activity-default';
    }
  }
}
