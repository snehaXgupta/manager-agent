<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Create and store a new notification.
     */
    public function createNotification(int $userId, string $type, string $severity, string $title, string $message): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'severity' => $severity, // 'INFO', 'WARNING', 'CRITICAL'
            'title' => $title,
            'message' => $message,
            'is_read' => false
        ]);
    }

    /**
     * Get unread notifications count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)->where('is_read', false)->count();
    }

    /**
     * Fetch notifications for a user.
     */
    public function getNotificationsForUser(int $userId, int $limit = 50)
    {
        return Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllRead(int $userId): void
    {
        Notification::where('user_id', $userId)->where('is_read', false)->update(['is_read' => true]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(int $notificationId): void
    {
        Notification::where('id', $notificationId)->update(['is_read' => true]);
    }
}
