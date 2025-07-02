import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface Employee {
  id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  address: string;
  job_title: string;
  department_id: string;
  status: string;
  scheduled_activation_date?: string;
  created_at: string;
  updated_at: string;
  department_name: string;
}

export interface ApiResponse<T> {
  status: string;
  data: T;
  message?: string;
}

@Injectable({
  providedIn: 'root'
})
export class EmployeeService {
  private apiUrl = 'http://localhost/Projects/Project/backend/ems-api/public/api';

  constructor(private http: HttpClient) {}

  getEmployees(): Observable<ApiResponse<Employee[]>> {
    return this.http.get<ApiResponse<Employee[]>>(`${this.apiUrl}/employees`);
  }

  getEmployee(id: string): Observable<ApiResponse<Employee>> {
    return this.http.get<ApiResponse<Employee>>(`${this.apiUrl}/employees/${id}`);
  }

  createEmployee(employee: Partial<Employee>): Observable<ApiResponse<Employee>> {
    return this.http.post<ApiResponse<Employee>>(`${this.apiUrl}/employees`, employee);
  }

  updateEmployee(id: string, employee: Partial<Employee>): Observable<ApiResponse<Employee>> {
    return this.http.put<ApiResponse<Employee>>(`${this.apiUrl}/employees/${id}`, employee);
  }

  deleteEmployee(id: string): Observable<ApiResponse<any>> {
    return this.http.delete<ApiResponse<any>>(`${this.apiUrl}/employees/${id}`);
  }

  getEmployeesByDepartment(departmentId: string): Observable<ApiResponse<Employee[]>> {
    return this.http.get<ApiResponse<Employee[]>>(`${this.apiUrl}/employees/department/${departmentId}`);
  }
}
