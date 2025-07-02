<?php

namespace App\Controllers;

use App\Models\ActivityLogModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class ActivityLogController extends ResourceController
{
    use ResponseTrait;

    protected $activityLogModel;

    public function __construct()
    {
        $this->activityLogModel = new ActivityLogModel();
        helper('jwt');
    }

    /**
     * Return an array of activity logs
     */
    public function index()
    {
        try {
            $logs = $this->activityLogModel->getLogsWithUser();
            
            return $this->respond([
                'status' => 'success',
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch activity logs: ' . $e->getMessage());
        }
    }

    /**
     * Return the properties of a resource object
     */
    public function show($id = null)
    {
        try {
            $log = $this->activityLogModel->getLogWithUser($id);
            
            if (!$log) {
                return $this->failNotFound('Activity log not found');
            }

            return $this->respond([
                'status' => 'success',
                'data' => $log
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch activity log: ' . $e->getMessage());
        }
    }

    /**
     * Create a new activity log
     */
    public function create()
    {
        $rules = [
            'user_id' => 'required|integer|is_not_unique[users.id]',
            'action' => 'required|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'user_id' => $this->request->getPost('user_id'),
            'action' => $this->request->getPost('action'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            $id = $this->activityLogModel->insert($data);
            
            if ($id) {
                $log = $this->activityLogModel->getLogWithUser($id);
                return $this->respondCreated([
                    'status' => 'success',
                    'message' => 'Activity log created successfully',
                    'data' => $log
                ]);
            } else {
                return $this->fail('Failed to create activity log');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to create activity log: ' . $e->getMessage());
        }
    }

    /**
     * Delete the designated resource object from the model
     */
    public function delete($id = null)
    {
        $log = $this->activityLogModel->find($id);
        
        if (!$log) {
            return $this->failNotFound('Activity log not found');
        }

        try {
            $this->activityLogModel->delete($id);
            
            return $this->respondDeleted([
                'status' => 'success',
                'message' => 'Activity log deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to delete activity log: ' . $e->getMessage());
        }
    }

    /**
     * Get activity logs for a specific user
     */
    public function byUser($userId = null)
    {
        try {
            $logs = $this->activityLogModel->where('user_id', $userId)
                                         ->orderBy('created_at', 'DESC')
                                         ->findAll();
            
            return $this->respond([
                'status' => 'success',
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch activity logs: ' . $e->getMessage());
        }
    }

    /**
     * Get current user's activity logs
     */
    public function myLogs()
    {
        $userId = getCurrentUserId($this->request);
        
        if (!$userId) {
            return $this->failUnauthorized('Access denied');
        }

        try {
            $logs = $this->activityLogModel->where('user_id', $userId)
                                         ->orderBy('created_at', 'DESC')
                                         ->findAll();
            
            return $this->respond([
                'status' => 'success',
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch activity logs: ' . $e->getMessage());
        }
    }

    /**
     * Get activity logs with pagination and filtering
     */
    public function paginated()
    {
        $page = $this->request->getGet('page') ?? 1;
        $perPage = $this->request->getGet('per_page') ?? 10;
        $search = $this->request->getGet('search') ?? '';
        $userId = $this->request->getGet('user_id') ?? '';
        $dateFrom = $this->request->getGet('date_from') ?? '';
        $dateTo = $this->request->getGet('date_to') ?? '';

        try {
            $builder = $this->activityLogModel->select('activity_logs.*, users.username, users.full_name')
                                            ->join('users', 'users.id = activity_logs.user_id', 'left');
            
            if (!empty($search)) {
                $builder = $builder->groupStart()
                    ->like('activity_logs.action', $search)
                    ->orLike('users.username', $search)
                    ->orLike('users.full_name', $search)
                    ->groupEnd();
            }

            if (!empty($userId)) {
                $builder = $builder->where('activity_logs.user_id', $userId);
            }

            if (!empty($dateFrom)) {
                $builder = $builder->where('activity_logs.created_at >=', $dateFrom . ' 00:00:00');
            }

            if (!empty($dateTo)) {
                $builder = $builder->where('activity_logs.created_at <=', $dateTo . ' 23:59:59');
            }

            $logs = $builder->orderBy('activity_logs.created_at', 'DESC')
                          ->paginate($perPage, 'default', $page);
            $pager = $this->activityLogModel->pager;

            return $this->respond([
                'status' => 'success',
                'data' => $logs,
                'pagination' => [
                    'current_page' => $pager->getCurrentPage(),
                    'per_page' => $pager->getPerPage(),
                    'total' => $pager->getTotal(),
                    'total_pages' => $pager->getPageCount()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch activity logs: ' . $e->getMessage());
        }
    }

    /**
     * Log an activity (helper method for other controllers)
     */
    public function logActivity($userId, $action)
    {
        try {
            $data = [
                'user_id' => $userId,
                'action' => $action,
                'created_at' => date('Y-m-d H:i:s')
            ];

            return $this->activityLogModel->insert($data);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get activity statistics
     */
    public function statistics()
    {
        try {
            // Get total activities
            $totalActivities = $this->activityLogModel->countAll();

            // Get activities from last 7 days
            $last7Days = $this->activityLogModel->where('created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))
                                               ->countAllResults();

            // Get activities from today
            $today = $this->activityLogModel->where('DATE(created_at)', date('Y-m-d'))
                                           ->countAllResults();

            // Get most active users (top 5)
            $activeUsers = $this->activityLogModel->select('users.username, users.full_name, COUNT(activity_logs.id) as activity_count')
                                                 ->join('users', 'users.id = activity_logs.user_id', 'left')
                                                 ->groupBy('activity_logs.user_id')
                                                 ->orderBy('activity_count', 'DESC')
                                                 ->limit(5)
                                                 ->findAll();

            // Get recent activities (last 10)
            $recentActivities = $this->activityLogModel->getLogsWithUser(10);

            return $this->respond([
                'status' => 'success',
                'data' => [
                    'total_activities' => $totalActivities,
                    'last_7_days' => $last7Days,
                    'today' => $today,
                    'most_active_users' => $activeUsers,
                    'recent_activities' => $recentActivities
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch activity statistics: ' . $e->getMessage());
        }
    }

    /**
     * Clear old activity logs (older than specified days)
     */
    public function cleanup()
    {
        $days = $this->request->getPost('days') ?? 30;

        if ($days < 1) {
            return $this->fail('Days must be at least 1');
        }

        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $deletedCount = $this->activityLogModel->where('created_at <', $cutoffDate)->delete();

            return $this->respond([
                'status' => 'success',
                'message' => "Cleanup completed. {$deletedCount} old activity logs removed.",
                'data' => ['deleted_count' => $deletedCount]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to cleanup activity logs: ' . $e->getMessage());
        }
    }
}
