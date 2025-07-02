import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface DashboardData {
  overview: {
    total_users: number;
    total_employees: number;
    total_departments: number;
    active_employees: number;
    inactive_employees: number;
  };
  recent_stats: {
    new_users_30_days: number;
    new_employees_30_days: number;
  };
  employee_status_breakdown: Array<{
    status: string;
    count: string;
  }>;
  department_stats: Array<{
    id: string;
    name: string;
    created_at: string;
    employee_count: string;
  }>;
  recent_activities: Array<{
    id: string;
    user_id: string;
    action: string;
    created_at: string;
    username: string;
    full_name: string;
  }>;
  activity_trend: Array<{
    date: string;
    count: number;
  }>;
}

export interface ApiResponse<T> {
  status: string;
  data: T;
  message?: string;
}

@Injectable({
  providedIn: 'root'
})
export class DashboardService {
  private apiUrl = 'http://localhost/Projects/Project/backend/ems-api/public/api';

  constructor(private http: HttpClient) {}

  getDashboardData(): Observable<ApiResponse<DashboardData>> {
    return this.http.get<ApiResponse<DashboardData>>(`${this.apiUrl}/dashboard`);
  }

  getUserDashboard(): Observable<ApiResponse<any>> {
    return this.http.get<ApiResponse<any>>(`${this.apiUrl}/dashboard/user`);
  }

  getAnalytics(): Observable<ApiResponse<any>> {
    return this.http.get<ApiResponse<any>>(`${this.apiUrl}/dashboard/analytics`);
  }
}
