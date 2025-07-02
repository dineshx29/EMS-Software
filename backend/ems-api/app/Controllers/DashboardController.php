<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\EmployeeModel;
use App\Models\DepartmentModel;
use App\Models\ActivityLogModel;
use App\Models\NotificationModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class DashboardController extends ResourceController
{
    use ResponseTrait;

    protected $userModel;
    protected $employeeModel;
    protected $departmentModel;
    protected $activityLogModel;
    protected $notificationModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->employeeModel = new EmployeeModel();
        $this->departmentModel = new DepartmentModel();
        $this->activityLogModel = new ActivityLogModel();
        $this->notificationModel = new NotificationModel();
        helper('jwt');
    }

    /**
     * Get dashboard statistics
     */
    public function index()
    {
        try {
            // Get basic counts
            $totalUsers = $this->userModel->countAll();
            $totalEmployees = $this->employeeModel->countAll();
            $totalDepartments = $this->departmentModel->countAll();
            $activeEmployees = $this->employeeModel->where('status', 'active')->countAllResults();
            $inactiveEmployees = $this->employeeModel->where('status', 'inactive')->countAllResults();

            // Get recent activities
            $recentActivities = $this->activityLogModel->getLogsWithUser(5);

            // Get employee status breakdown
            $employeeStatusStats = $this->employeeModel->select('status, COUNT(*) as count')
                                                      ->groupBy('status')
                                                      ->findAll();

            // Get department employee counts
            $departmentStats = $this->departmentModel->getDepartmentsWithEmployeeCount();

            // Get recent registrations (last 30 days)
            $recentUsers = $this->userModel->where('created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')))
                                          ->countAllResults();

            $recentEmployees = $this->employeeModel->where('created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')))
                                                  ->countAllResults();

            // Get activity trend (last 7 days)
            $activityTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $count = $this->activityLogModel->where('DATE(created_at)', $date)->countAllResults();
                $activityTrend[] = [
                    'date' => $date,
                    'count' => $count
                ];
            }

            return $this->respond([
                'status' => 'success',
                'data' => [
                    'overview' => [
                        'total_users' => $totalUsers,
                        'total_employees' => $totalEmployees,
                        'total_departments' => $totalDepartments,
                        'active_employees' => $activeEmployees,
                        'inactive_employees' => $inactiveEmployees
                    ],
                    'recent_stats' => [
                        'new_users_30_days' => $recentUsers,
                        'new_employees_30_days' => $recentEmployees
                    ],
                    'employee_status_breakdown' => $employeeStatusStats,
                    'department_stats' => $departmentStats,
                    'recent_activities' => $recentActivities,
                    'activity_trend' => $activityTrend
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch dashboard data: ' . $e->getMessage());
        }
    }

    /**
     * Get user-specific dashboard data
     */
    public function userDashboard()
    {
        $userId = getCurrentUserId($this->request);
        
        if (!$userId) {
            return $this->failUnauthorized('Access denied');
        }

        try {
            // Get user notifications
            $unreadNotifications = $this->notificationModel->where('user_id', $userId)
                                                         ->where('is_read', false)
                                                         ->countAllResults();

            $recentNotifications = $this->notificationModel->where('user_id', $userId)
                                                         ->orderBy('created_at', 'DESC')
                                                         ->limit(5)
                                                         ->findAll();

            // Get user activity logs
            $userActivities = $this->activityLogModel->where('user_id', $userId)
                                                   ->orderBy('created_at', 'DESC')
                                                   ->limit(10)
                                                   ->findAll();

            // Get user profile with roles
            $userProfile = $this->userModel->getUserWithRolesAndPermissions($userId);
            unset($userProfile['password']);

            return $this->respond([
                'status' => 'success',
                'data' => [
                    'user_profile' => $userProfile,
                    'notifications' => [
                        'unread_count' => $unreadNotifications,
                        'recent' => $recentNotifications
                    ],
                    'recent_activities' => $userActivities
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch user dashboard data: ' . $e->getMessage());
        }
    }

    /**
     * Get analytics data
     */
    public function analytics()
    {
        try {
            // Employee growth over last 12 months
            $employeeGrowth = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-{$i} months"));
                $count = $this->employeeModel->where('DATE_FORMAT(created_at, "%Y-%m") <=', $date)
                                            ->countAllResults();
                $employeeGrowth[] = [
                    'month' => $date,
                    'count' => $count
                ];
            }

            // User registration trend (last 12 months)
            $userGrowth = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-{$i} months"));
                $count = $this->userModel->where('DATE_FORMAT(created_at, "%Y-%m") <=', $date)
                                        ->countAllResults();
                $userGrowth[] = [
                    'month' => $date,
                    'count' => $count
                ];
            }

            // Activity by hour (last 24 hours)
            $hourlyActivity = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $count = $this->activityLogModel->where('HOUR(created_at)', $hour)
                                               ->where('DATE(created_at)', date('Y-m-d'))
                                               ->countAllResults();
                $hourlyActivity[] = [
                    'hour' => sprintf('%02d:00', $hour),
                    'count' => $count
                ];
            }

            // Top departments by employee count
            $topDepartments = $this->departmentModel->select('departments.name, COUNT(employees.id) as employee_count')
                                                   ->join('employees', 'employees.department_id = departments.id', 'left')
                                                   ->groupBy('departments.id')
                                                   ->orderBy('employee_count', 'DESC')
                                                   ->limit(10)
                                                   ->findAll();

            return $this->respond([
                'status' => 'success',
                'data' => [
                    'employee_growth' => $employeeGrowth,
                    'user_growth' => $userGrowth,
                    'hourly_activity' => $hourlyActivity,
                    'top_departments' => $topDepartments
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch analytics data: ' . $e->getMessage());
        }
    }

    /**
     * Get system health status
     */
    public function health()
    {
        try {
            // Database connection check
            $dbStatus = true;
            try {
                $this->userModel->countAll();
            } catch (\Exception $e) {
                $dbStatus = false;
            }

            // Get disk usage (if possible)
            $diskUsage = null;
            if (function_exists('disk_free_bytes') && function_exists('disk_total_bytes')) {
                $freeBytes = disk_free_bytes('/');
                $totalBytes = disk_total_bytes('/');
                $diskUsage = [
                    'free' => $freeBytes,
                    'total' => $totalBytes,
                    'used_percentage' => round(($totalBytes - $freeBytes) / $totalBytes * 100, 2)
                ];
            }

            // Memory usage
            $memoryUsage = [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit')
            ];

            // Get application version
            $version = '1.0.0'; // You can get this from a config file

            return $this->respond([
                'status' => 'success',
                'data' => [
                    'system_status' => 'healthy',
                    'database_status' => $dbStatus ? 'connected' : 'disconnected',
                    'version' => $version,
                    'uptime' => time() - $_SERVER['REQUEST_TIME'],
                    'memory_usage' => $memoryUsage,
                    'disk_usage' => $diskUsage,
                    'php_version' => PHP_VERSION,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch health status: ' . $e->getMessage());
        }
    }
}
