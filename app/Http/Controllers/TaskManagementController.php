<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TaskManagementController extends Controller
{
    /**
     * Store a newly created task assignment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'required|date',
            'assigned_to' => 'required|exists:users,id',
        ]);

        Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'deadline' => Carbon::parse($validated['deadline']),
            'assigned_to' => $validated['assigned_to'],
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Task assigned successfully to team member.');
    }

    /**
     * Update task status (e.g. mark complete or in progress).
     */
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $task->update([
            'status' => $validated['status']
        ]);

        return redirect()->back()->with('success', "Task status updated to '{$validated['status']}'.");
    }
}
