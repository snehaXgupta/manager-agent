<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationCenterController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of manager alerts.
     */
    public function index()
    {
        $userId = auth()->user()->id;
        $notifications = $this->notificationService->getNotificationsForUser($userId);

        return view('dashboard.notifications.index', compact('notifications'));
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead()
    {
        $userId = auth()->user()->id;
        $this->notificationService->markAllRead($userId);

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}
