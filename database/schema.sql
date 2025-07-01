-- APEX EMS Database Schema
-- Run this script in phpMyAdmin or MySQL command line

CREATE DATABASE IF NOT EXISTS apex_ems;
USE apex_ems;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'Employee',
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Employees table
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(50) NOT NULL,
    position VARCHAR(100) NOT NULL,
    status ENUM('Active', 'Inactive', 'On Leave') DEFAULT 'Active',
    join_date DATE NOT NULL,
    salary DECIMAL(10,2),
    manager VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Roles table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    permissions JSON,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Audit log table
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default data
INSERT INTO users (username, email, first_name, last_name, password, role, status) VALUES 
('admin', 'admin@apexems.com', 'System', 'Administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 'Active'),
('jsmith', 'john.smith@company.com', 'John', 'Smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 'Active'),
('sjohnson', 'sarah.johnson@company.com', 'Sarah', 'Johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HR Admin', 'Active');

INSERT INTO employees (employee_id, first_name, last_name, email, phone, department, position, status, join_date, salary, manager) VALUES 
('EMP001', 'John', 'Smith', 'john.smith@company.com', '+1 (555) 123-4567', 'Engineering', 'Senior Developer', 'Active', '2022-01-15', 85000.00, 'Sarah Johnson'),
('EMP002', 'Sarah', 'Johnson', 'sarah.johnson@company.com', '+1 (555) 234-5678', 'Engineering', 'Engineering Manager', 'Active', '2021-03-10', 120000.00, 'Michael Brown'),
('EMP003', 'Michael', 'Brown', 'michael.brown@company.com', '+1 (555) 345-6789', 'Human Resources', 'HR Director', 'Active', '2020-08-22', 95000.00, 'Emily Davis'),
('EMP004', 'Emily', 'Davis', 'emily.davis@company.com', '+1 (555) 456-7890', 'Sales', 'Sales Representative', 'Active', '2023-02-14', 65000.00, 'John Smith'),
('EMP005', 'Robert', 'Wilson', 'robert.wilson@company.com', '+1 (555) 567-8901', 'Finance', 'Financial Analyst', 'Inactive', '2021-11-05', 70000.00, 'Sarah Johnson');

INSERT INTO roles (name, description, permissions, status) VALUES 
('Super Admin', 'Full system access with all permissions', '["all"]', 'Active'),
('Manager', 'Department management and employee oversight', '["employees.read", "employees.write", "reports.read"]', 'Active'),
('HR Admin', 'Human resources administration', '["employees.read", "employees.write", "users.read"]', 'Active'),
('Employee', 'Basic employee access', '["profile.read", "profile.write"]', 'Active'),
('Viewer', 'Read-only access to reports', '["reports.read"]', 'Active');

-- Create indexes for better performance
CREATE INDEX idx_employees_department ON employees(department);
CREATE INDEX idx_employees_status ON employees(status);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);
