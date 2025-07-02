<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table = 'activity_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['user_id', 'action', 'created_at'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $deletedField = '';

    // Validation
    protected $validationRules = [
        'user_id' => 'permit_empty|is_natural_no_zero',
        'action' => 'required|max_length[255]'
    ];

    protected $validationMessages = [
        'user_id' => [
            'is_natural_no_zero' => 'Please provide a valid user ID'
        ],
        'action' => [
            'required' => 'Action description is required',
            'max_length' => 'Action description cannot exceed 255 characters'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Log an activity
     */
    public function logActivity($userId, $action)
    {
        return $this->insert([
            'user_id' => $userId,
            'action' => $action,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get activity logs with user information
     */
    public function getActivityLogsWithUser($limit = 50, $offset = 0)
    {
        return $this->select('activity_logs.*, users.username, users.full_name')
                    ->join('users', 'users.id = activity_logs.user_id', 'left')
                    ->orderBy('activity_logs.created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get activity logs for a specific user
     */
    public function getUserActivityLogs($userId, $limit = 50, $offset = 0)
    {
        return $this->select('activity_logs.*, users.username, users.full_name')
                    ->join('users', 'users.id = activity_logs.user_id', 'left')
                    ->where('activity_logs.user_id', $userId)
                    ->orderBy('activity_logs.created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get activity logs by date range
     */
    public function getActivityLogsByDateRange($startDate, $endDate, $limit = 50, $offset = 0)
    {
        return $this->select('activity_logs.*, users.username, users.full_name')
                    ->join('users', 'users.id = activity_logs.user_id', 'left')
                    ->where('activity_logs.created_at >=', $startDate)
                    ->where('activity_logs.created_at <=', $endDate)
                    ->orderBy('activity_logs.created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Search activity logs
     */
    public function searchActivityLogs($searchTerm, $limit = 50, $offset = 0)
    {
        return $this->select('activity_logs.*, users.username, users.full_name')
                    ->join('users', 'users.id = activity_logs.user_id', 'left')
                    ->groupStart()
                        ->like('activity_logs.action', $searchTerm)
                        ->orLike('users.username', $searchTerm)
                        ->orLike('users.full_name', $searchTerm)
                    ->groupEnd()
                    ->orderBy('activity_logs.created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats($days = 30)
    {
        $db = \Config\Database::connect();
        
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stats = [];
        
        // Total activities in the last X days
        $stats['total_activities'] = $this->where('created_at >=', $startDate)->countAllResults(false);
        
        // Activities per day
        $builder = $db->table('activity_logs');
        $stats['daily_activities'] = $builder->select('DATE(created_at) as date, COUNT(*) as count')
                                           ->where('created_at >=', $startDate)
                                           ->groupBy('DATE(created_at)')
                                           ->orderBy('date', 'ASC')
                                           ->get()
                                           ->getResultArray();
        
        // Top users by activity
        $builder = $db->table('activity_logs al');
        $stats['top_users'] = $builder->select('u.username, u.full_name, COUNT(al.id) as activity_count')
                                    ->join('users u', 'u.id = al.user_id', 'left')
                                    ->where('al.created_at >=', $startDate)
                                    ->groupBy('al.user_id')
                                    ->orderBy('activity_count', 'DESC')
                                    ->limit(10)
                                    ->get()
                                    ->getResultArray();
        
        return $stats;
    }

    /**
     * Clean old activity logs
     */
    public function cleanOldLogs($daysToKeep = 90)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        return $this->where('created_at <', $cutoffDate)->delete();
    }

    public function getLogsWithUser($limit = null)
    {
        $builder = $this->select('activity_logs.*, users.username, users.full_name')
                       ->join('users', 'users.id = activity_logs.user_id', 'left')
                       ->orderBy('activity_logs.created_at', 'DESC');
        
        if ($limit) {
            $builder = $builder->limit($limit);
        }
        
        return $builder->findAll();
    }

    public function getLogWithUser($logId)
    {
        return $this->select('activity_logs.*, users.username, users.full_name')
                   ->join('users', 'users.id = activity_logs.user_id', 'left')
                   ->where('activity_logs.id', $logId)
                   ->first();
    }

    public function getLogsByUser($userId, $limit = null)
    {
        $builder = $this->where('user_id', $userId)
                       ->orderBy('created_at', 'DESC');
        
        if ($limit) {
            $builder = $builder->limit($limit);
        }
        
        return $builder->findAll();
    }

    public function getLogsByDateRange($startDate, $endDate)
    {
        return $this->select('activity_logs.*, users.username, users.full_name')
                   ->join('users', 'users.id = activity_logs.user_id', 'left')
                   ->where('activity_logs.created_at >=', $startDate)
                   ->where('activity_logs.created_at <=', $endDate)
                   ->orderBy('activity_logs.created_at', 'DESC')
                   ->findAll();
    }

    public function getActivityStatistics()
    {
        $total = $this->countAll();
        $today = $this->where('DATE(created_at)', date('Y-m-d'))->countAllResults();
        $thisWeek = $this->where('created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))->countAllResults();
        $thisMonth = $this->where('created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')))->countAllResults();

        return [
            'total' => $total,
            'today' => $today,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth
        ];
    }

    public function getMostActiveUsers($limit = 10)
    {
        return $this->select('users.username, users.full_name, COUNT(activity_logs.id) as activity_count')
                   ->join('users', 'users.id = activity_logs.user_id', 'left')
                   ->groupBy('activity_logs.user_id')
                   ->orderBy('activity_count', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    public function cleanupOldLogs($days = 90)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->where('created_at <', $cutoffDate)->delete();
    }
}
