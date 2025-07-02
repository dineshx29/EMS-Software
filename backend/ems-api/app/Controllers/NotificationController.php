<?php

namespace App\Controllers;

use App\Models\NotificationModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class NotificationController extends ResourceController
{
    use ResponseTrait;

    protected $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
        helper('jwt');
    }

    /**
     * Return notifications for current user
     */
    public function index()
    {
        $userId = getCurrentUserId($this->request);
        
        if (!$userId) {
            return $this->failUnauthorized('Access denied');
        }

        try {
            $notifications = $this->notificationModel->where('user_id', $userId)
                                                   ->orderBy('created_at', 'DESC')
                                                   ->findAll();
            
            return $this->respond([
                'status' => 'success',
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch notifications: ' . $e->getMessage());
        }
    }

    /**
     * Return unread notifications for current user
     */
    public function unread()
    {
        $userId = getCurrentUserId($this->request);
        
        if (!$userId) {
            return $this->failUnauthorized('Access denied');
        }

        try {
            $notifications = $this->notificationModel->where('user_id', $userId)
                                                   ->where('is_read', false)
                                                   ->orderBy('created_at', 'DESC')
                                                   ->findAll();
            
            $count = count($notifications);
            
            return $this->respond([
                'status' => 'success',
                'data' => [
                    'notifications' => $notifications,
                    'unread_count' => $count
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch notifications: ' . $e->getMessage());
        }
    }

    /**
     * Return the properties of a resource object
     */
    public function show($id = null)
    {
        $userId = getCurrentUserId($this->request);
        
        if (!$userId) {
            return $this->failUnauthorized('Access denied');
        }

        try {
            $notification = $this->notificationModel->where('id', $id)
                                                  ->where('user_id', $userId)
                                                  ->first();
            
            if (!$notification) {
                return $this->failNotFound('Notification not found');
            }

            return $this->respond([
                'status' => 'success',
                'data' => $notification
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch notification: ' . $e->getMessage());
        }
    }

    /**
     * Create a new notification
     */
    public function create()
    {
        $rules = [
            'user_id' => 'required|integer|is_not_unique[users.id]',
            'type' => 'required|in_list[success,warning,error,info]',
            'message' => 'required|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = [
            'user_id' => $this->request->getPost('user_id'),
            'type' => $this->request->getPost('type'),
            'message' => $this->request->getPost('message'),
            'is_read' => false,
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            $id = $this->notificationModel->insert($data);
            
            if ($id) {
                $notification = $this->notificationModel->find($id);
                return $this->respondCreated([
                    'status' => 'success',
                    'message' => 'Notification created successfully',
                    'data' => $notification
                ]);
            } else {
                return $this->fail('Failed to create notification');
            }
        } catch (\Exception $e) {
            return $this->fail('Failed to create notification: ' . $e->getMessage());
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id = null)
    {
        $userId = getCurrentUserId($this->request);
        
        if (!$userId) {
            return $this->failUnauthorized('Access denied');
        }

        try {
            $notification = $this->notificationModel->where('id', $id)
                                                  ->where('user_id', $userId)
                                                  ->first();
            
            if (!$notification) {
                return $this->failNotFound('Notification not found');
            }

            $this->notificationModel->update($id, ['is_read' => true]);
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to mark notification as read: ' . $e->getMessage());
        }
    }

    /**
     * Mark all notifications as read for current user
     */
    public function markAllAsRead()
    {
        $userId = getCurrentUserId($this->request);
        
        if (!$userId) {
            return $this->failUnauthorized('Access denied');
        }

        try {
            $this->notificationModel->where('user_id', $userId)
                                   ->where('is_read', false)
                                   ->set(['is_read' => true])
                                   ->update();
            
            return $this->respond([
                'status' => 'success',
                'message' => 'All notifications marked as read'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to mark notifications as read: ' . $e->getMessage());
        }
    }

    /**
     * Delete notification
     */
    public function delete($id = null)
    {
        $userId = getCurrentUserId($this->request);
        
        if (!$userId) {
            return $this->failUnauthorized('Access denied');
        }

        try {
            $notification = $this->notificationModel->where('id', $id)
                                                  ->where('user_id', $userId)
                                                  ->first();
            
            if (!$notification) {
                return $this->failNotFound('Notification not found');
            }

            $this->notificationModel->delete($id);
            
            return $this->respondDeleted([
                'status' => 'success',
                'message' => 'Notification deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to delete notification: ' . $e->getMessage());
        }
    }

    /**
     * Get notifications with pagination
     */
    public function paginated()
    {
        $userId = getCurrentUserId($this->request);
        
        if (!$userId) {
            return $this->failUnauthorized('Access denied');
        }

        $page = $this->request->getGet('page') ?? 1;
        $perPage = $this->request->getGet('per_page') ?? 10;
        $type = $this->request->getGet('type') ?? '';
        $isRead = $this->request->getGet('is_read') ?? '';

        try {
            $builder = $this->notificationModel->where('user_id', $userId);
            
            if (!empty($type)) {
                $builder = $builder->where('type', $type);
            }

            if ($isRead !== '') {
                $builder = $builder->where('is_read', $isRead === 'true');
            }

            $notifications = $builder->orderBy('created_at', 'DESC')
                                   ->paginate($perPage, 'default', $page);
            $pager = $this->notificationModel->pager;

            return $this->respond([
                'status' => 'success',
                'data' => $notifications,
                'pagination' => [
                    'current_page' => $pager->getCurrentPage(),
                    'per_page' => $pager->getPerPage(),
                    'total' => $pager->getTotal(),
                    'total_pages' => $pager->getPageCount()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch notifications: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to multiple users
     */
    public function broadcast()
    {
        $rules = [
            'user_ids' => 'required|is_array',
            'user_ids.*' => 'required|integer|is_not_unique[users.id]',
            'type' => 'required|in_list[success,warning,error,info]',
            'message' => 'required|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $userIds = $this->request->getPost('user_ids');
        $type = $this->request->getPost('type');
        $message = $this->request->getPost('message');

        try {
            $notifications = [];
            foreach ($userIds as $userId) {
                $notifications[] = [
                    'user_id' => $userId,
                    'type' => $type,
                    'message' => $message,
                    'is_read' => false,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }

            $this->notificationModel->insertBatch($notifications);
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Notifications sent successfully',
                'data' => ['sent_count' => count($notifications)]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to send notifications: ' . $e->getMessage());
        }
    }
}
