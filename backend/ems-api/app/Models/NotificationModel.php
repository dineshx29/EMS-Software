<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['user_id', 'type', 'message', 'is_read', 'created_at'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $deletedField = '';

    // Validation
    protected $validationRules = [
        'user_id' => 'permit_empty|is_natural_no_zero',
        'type' => 'required|in_list[success,warning,error,info]',
        'message' => 'required|max_length[255]',
        'is_read' => 'in_list[0,1]'
    ];

    protected $validationMessages = [
        'user_id' => [
            'is_natural_no_zero' => 'Please provide a valid user ID'
        ],
        'type' => [
            'required' => 'Notification type is required',
            'in_list' => 'Type must be success, warning, error, or info'
        ],
        'message' => [
            'required' => 'Message is required',
            'max_length' => 'Message cannot exceed 255 characters'
        ],
        'is_read' => [
            'in_list' => 'Is read must be 0 or 1'
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
     * Create a notification
     */
    public function createNotification($userId, $type, $message)
    {
        return $this->insert([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get notifications for a user
     */
    public function getUserNotifications($userId, $limit = 50, $offset = 0)
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications($userId)
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount($userId)
    {
        return $this->where('user_id', $userId)
                   ->where('is_read', 0)
                   ->countAllResults();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId = null)
    {
        $builder = $this->where('id', $notificationId);
        
        if ($userId) {
            $builder->where('user_id', $userId);
        }
        
        return $builder->set('is_read', 1)->update();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId)
    {
        return $this->where('user_id', $userId)
                   ->where('is_read', 0)
                   ->set('is_read', 1)
                   ->update();
    }

    /**
     * Delete notification
     */
    public function deleteNotification($notificationId, $userId = null)
    {
        $builder = $this->where('id', $notificationId);
        
        if ($userId) {
            $builder->where('user_id', $userId);
        }
        
        return $builder->delete();
    }

    /**
     * Delete all read notifications for a user
     */
    public function deleteReadNotifications($userId)
    {
        return $this->where('user_id', $userId)
                   ->where('is_read', 1)
                   ->delete();
    }

    /**
     * Broadcast notification to all users
     */
    public function broadcastNotification($type, $message, $excludeUserIds = [])
    {
        $userModel = new UserModel();
        $users = $userModel->where('status', 'active')->findAll();
        
        $notifications = [];
        foreach ($users as $user) {
            if (!in_array($user['id'], $excludeUserIds)) {
                $notifications[] = [
                    'user_id' => $user['id'],
                    'type' => $type,
                    'message' => $message,
                    'is_read' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        if (!empty($notifications)) {
            return $this->insertBatch($notifications);
        }
        
        return true;
    }

    /**
     * Send notification to specific users
     */
    public function sendToUsers($userIds, $type, $message)
    {
        $notifications = [];
        foreach ($userIds as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'type' => $type,
                'message' => $message,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        if (!empty($notifications)) {
            return $this->insertBatch($notifications);
        }
        
        return true;
    }

    /**
     * Clean old notifications
     */
    public function cleanOldNotifications($daysToKeep = 30)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        return $this->where('created_at <', $cutoffDate)
                   ->where('is_read', 1)
                   ->delete();
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats($userId = null, $days = 30)
    {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stats = [];
        
        $builder = $this->where('created_at >=', $startDate);
        if ($userId) {
            $builder->where('user_id', $userId);
        }
        
        // Total notifications
        $stats['total'] = $builder->countAllResults(false);
        
        // Unread notifications
        $stats['unread'] = $builder->where('is_read', 0)->countAllResults(false);
        
        // Notifications by type
        $db = \Config\Database::connect();
        $typeBuilder = $db->table('notifications');
        $typeBuilder->select('type, COUNT(*) as count')
                   ->where('created_at >=', $startDate);
        
        if ($userId) {
            $typeBuilder->where('user_id', $userId);
        }
        
        $stats['by_type'] = $typeBuilder->groupBy('type')->get()->getResultArray();
        
        return $stats;
    }
}
