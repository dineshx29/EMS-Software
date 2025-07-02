<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// API Health Check
$routes->get('api', 'ApiController::index');
$routes->get('api/test', 'ApiController::dbTest');

// API Group
$routes->group('api', ['namespace' => 'App\Controllers'], function($routes) {
    
    // Authentication routes
    $routes->post('auth/login', 'AuthController::login');
    $routes->post('auth/register', 'AuthController::register');
    $routes->post('auth/logout', 'AuthController::logout');
    $routes->get('auth/profile', 'AuthController::profile');
    $routes->put('auth/profile', 'AuthController::updateProfile');
    $routes->get('auth/debug', 'AuthController::debug');

    // Dashboard routes
    $routes->get('dashboard', 'DashboardController::index');
    $routes->get('dashboard/user', 'DashboardController::userDashboard');
    $routes->get('dashboard/analytics', 'DashboardController::analytics');
    $routes->get('dashboard/health', 'DashboardController::health');

    // Department routes
    $routes->get('departments', 'DepartmentController::index');
    $routes->get('departments/paginated', 'DepartmentController::paginated');
    $routes->get('departments/(:num)', 'DepartmentController::show/$1');
    $routes->post('departments', 'DepartmentController::create');
    $routes->put('departments/(:num)', 'DepartmentController::update/$1');
    $routes->delete('departments/(:num)', 'DepartmentController::delete/$1');

    // Employee routes
    $routes->get('employees', 'EmployeeController::index');
    $routes->get('employees/paginated', 'EmployeeController::paginated');
    $routes->get('employees/(:num)', 'EmployeeController::show/$1');
    $routes->get('employees/department/(:num)', 'EmployeeController::byDepartment/$1');
    $routes->post('employees', 'EmployeeController::create');
    $routes->put('employees/(:num)', 'EmployeeController::update/$1');
    $routes->put('employees/(:num)/status', 'EmployeeController::updateStatus/$1');
    $routes->delete('employees/(:num)', 'EmployeeController::delete/$1');

    // User routes
    $routes->get('users', 'UserController::index');
    $routes->get('users/paginated', 'UserController::paginated');
    $routes->get('users/(:num)', 'UserController::show/$1');
    $routes->post('users', 'UserController::create');
    $routes->put('users/(:num)', 'UserController::update/$1');
    $routes->put('users/(:num)/roles', 'UserController::assignRoles/$1');
    $routes->put('users/(:num)/change-password', 'UserController::changePassword/$1');
    $routes->delete('users/(:num)', 'UserController::delete/$1');

    // Role routes
    $routes->get('roles', 'RoleController::index');
    $routes->get('roles/paginated', 'RoleController::paginated');
    $routes->get('roles/(:num)', 'RoleController::show/$1');
    $routes->post('roles', 'RoleController::create');
    $routes->put('roles/(:num)', 'RoleController::update/$1');
    $routes->put('roles/(:num)/permissions', 'RoleController::assignPermissions/$1');
    $routes->delete('roles/(:num)', 'RoleController::delete/$1');

    // Permission routes
    $routes->get('permissions', 'PermissionController::index');
    $routes->get('permissions/all', 'PermissionController::all');
    $routes->get('permissions/paginated', 'PermissionController::paginated');
    $routes->get('permissions/groups', 'PermissionController::groups');
    $routes->get('permissions/group/(:any)', 'PermissionController::byGroup/$1');
    $routes->get('permissions/(:num)', 'PermissionController::show/$1');
    $routes->post('permissions', 'PermissionController::create');
    $routes->put('permissions/(:num)', 'PermissionController::update/$1');
    $routes->delete('permissions/(:num)', 'PermissionController::delete/$1');

    // Notification routes
    $routes->get('notifications', 'NotificationController::index');
    $routes->get('notifications/unread', 'NotificationController::unread');
    $routes->get('notifications/paginated', 'NotificationController::paginated');
    $routes->get('notifications/(:num)', 'NotificationController::show/$1');
    $routes->post('notifications', 'NotificationController::create');
    $routes->post('notifications/broadcast', 'NotificationController::broadcast');
    $routes->put('notifications/(:num)/read', 'NotificationController::markAsRead/$1');
    $routes->put('notifications/mark-all-read', 'NotificationController::markAllAsRead');
    $routes->delete('notifications/(:num)', 'NotificationController::delete/$1');

    // Activity Log routes
    $routes->get('activity-logs', 'ActivityLogController::index');
    $routes->get('activity-logs/paginated', 'ActivityLogController::paginated');
    $routes->get('activity-logs/my-logs', 'ActivityLogController::myLogs');
    $routes->get('activity-logs/user/(:num)', 'ActivityLogController::byUser/$1');
    $routes->get('activity-logs/statistics', 'ActivityLogController::statistics');
    $routes->get('activity-logs/(:num)', 'ActivityLogController::show/$1');
    $routes->post('activity-logs', 'ActivityLogController::create');
    $routes->post('activity-logs/cleanup', 'ActivityLogController::cleanup');
    $routes->delete('activity-logs/(:num)', 'ActivityLogController::delete/$1');

    // Test routes for debugging
    $routes->get('debug/data', 'TestController::index');
    $routes->get('debug/connection', 'TestController::connection');
    $routes->get('debug/table/(:any)', 'TestController::table/$1');
});
