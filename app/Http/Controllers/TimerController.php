<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TimerController extends Controller
{
    /**
     * Start a timer for a user on a specific task.
     */
    public function start(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $user = auth()->user();
        $targetUserId = (int) $validated['user_id'];
        
        if ($user->role !== 'admin' && (int)$user->id !== $targetUserId) {
            $targetUser = User::find($targetUserId);
            if (!$targetUser || (int)$targetUser->manager_id !== (int)$user->id) {
                return response()->json([
                    'error' => 'Forbidden. You do not have permission to control timers for this user.'
                ], 403);
            }
        }

        // Check for existing active timer
        $activeTimer = TimeEntry::where('user_id', $validated['user_id'])
            ->whereNull('stopped_at')
            ->first();

        if ($activeTimer) {
            return response()->json([
                'error' => 'An active timer is already running for this user. Please stop it first.',
                'active_timer' => $activeTimer
            ], 400);
        }

        // Start new timer
        $timeEntry = TimeEntry::create([
            'task_id' => $validated['task_id'],
            'user_id' => $validated['user_id'],
            'started_at' => Carbon::now(),
        ]);

        // Transition task status to in_progress if pending
        $task = Task::find($validated['task_id']);
        if ($task->status === 'pending') {
            $task->update(['status' => 'in_progress']);
        }

        return response()->json([
            'message' => 'Timer started successfully.',
            'time_entry' => $timeEntry
        ], 201);
    }

    /**
     * Stop the active timer for a user.
     */
    public function stop(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = auth()->user();
        $targetUserId = (int) $validated['user_id'];
        
        if ($user->role !== 'admin' && (int)$user->id !== $targetUserId) {
            $targetUser = User::find($targetUserId);
            if (!$targetUser || (int)$targetUser->manager_id !== (int)$user->id) {
                return response()->json([
                    'error' => 'Forbidden. You do not have permission to control timers for this user.'
                ], 403);
            }
        }

        // Find active timer
        $activeTimer = TimeEntry::where('user_id', $validated['user_id'])
            ->whereNull('stopped_at')
            ->first();

        if (!$activeTimer) {
            return response()->json([
                'error' => 'No active timer found for this user.'
            ], 404);
        }

        $stoppedAt = Carbon::now();
        $startedAt = Carbon::parse($activeTimer->started_at);
        $durationSeconds = (int) abs($stoppedAt->diffInSeconds($startedAt));

        $activeTimer->update([
            'stopped_at' => $stoppedAt,
            'duration_seconds' => $durationSeconds,
        ]);

        return response()->json([
            'message' => 'Timer stopped successfully.',
            'time_entry' => $activeTimer
        ], 200);
    }
}
