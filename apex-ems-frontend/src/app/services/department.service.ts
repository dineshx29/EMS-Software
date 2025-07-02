import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface Department {
  id: string;
  name: string;
  created_at: string;
  employee_count: string;
}

export interface ApiResponse<T> {
  status: string;
  data: T;
  message?: string;
}

@Injectable({
  providedIn: 'root'
})
export class DepartmentService {
  private apiUrl = 'http://localhost/Projects/Project/backend/ems-api/public/api';

  constructor(private http: HttpClient) {}

  getDepartments(): Observable<ApiResponse<Department[]>> {
    return this.http.get<ApiResponse<Department[]>>(`${this.apiUrl}/departments`);
  }

  getDepartment(id: string): Observable<ApiResponse<Department>> {
    return this.http.get<ApiResponse<Department>>(`${this.apiUrl}/departments/${id}`);
  }

  createDepartment(department: Partial<Department>): Observable<ApiResponse<Department>> {
    return this.http.post<ApiResponse<Department>>(`${this.apiUrl}/departments`, department);
  }

  updateDepartment(id: string, department: Partial<Department>): Observable<ApiResponse<Department>> {
    return this.http.put<ApiResponse<Department>>(`${this.apiUrl}/departments/${id}`, department);
  }

  deleteDepartment(id: string): Observable<ApiResponse<any>> {
    return this.http.delete<ApiResponse<any>>(`${this.apiUrl}/departments/${id}`);
  }
}
