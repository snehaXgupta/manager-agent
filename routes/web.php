<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EmployeeDashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ManagerDashboardController;
use App\Http\Controllers\TaskManagementController;
use App\Http\Controllers\RiskCenterController;
use App\Http\Controllers\TeamHealthController;
use App\Http\Controllers\WorkloadAnalysisController;
use App\Http\Controllers\NotificationCenterController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\DeveloperController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        $activeRole = session('active_role', auth()->user()->role);
        if ($activeRole === 'manager') {
            return redirect()->route('dashboard.index');
        } elseif ($activeRole === 'employee') {
            return redirect()->route('employee.dashboard');
        } elseif ($activeRole === 'admin') {
            return redirect()->route('admin.index');
        }
    }
    return redirect()->route('login');
});

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Auth Protected Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/switch-role', [AuthController::class, 'switchRole'])->name('role.switch');

    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
        Route::post('/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
        Route::post('/git', [SettingsController::class, 'updateGitAccounts'])->name('settings.git');
        Route::post('/fireflies/regenerate', [SettingsController::class, 'regenerateWebhookSecret'])->name('settings.fireflies.regenerate');
    });

    // Timer endpoints (Web/Session context)
    Route::post('/web/timer/start', [\App\Http\Controllers\TimerController::class, 'start']);
    Route::post('/web/timer/stop', [\App\Http\Controllers\TimerController::class, 'stop']);

    // AI Chat Manager Agent
    Route::prefix('ai-agent')->group(function () {
        Route::get('/', [\App\Http\Controllers\AiChatController::class, 'index'])->name('dashboard.ai-chat.index');
        Route::post('/message', [\App\Http\Controllers\AiChatController::class, 'sendMessage'])->name('dashboard.ai-chat.send');
        Route::post('/clear/{id}', [\App\Http\Controllers\AiChatController::class, 'clearConversation'])->name('dashboard.ai-chat.clear');
        Route::delete('/delete/{id}', [\App\Http\Controllers\AiChatController::class, 'destroy'])->name('dashboard.ai-chat.destroy');
        Route::get('/export/{id}', [\App\Http\Controllers\AiChatController::class, 'exportChat'])->name('dashboard.ai-chat.export');
    });

    // Admin Group
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin.index');
        Route::post('/users', [AdminController::class, 'store'])->name('admin.users.store');

        // Departments CRUD
        Route::post('/departments', [AdminController::class, 'storeDepartment'])->name('admin.departments.store');
        Route::patch('/departments/{id}', [AdminController::class, 'updateDepartment'])->name('admin.departments.update');
        Route::delete('/departments/{id}', [AdminController::class, 'destroyDepartment'])->name('admin.departments.destroy');

        // Designations CRUD
        Route::post('/designations', [AdminController::class, 'storeDesignation'])->name('admin.designations.store');
        Route::patch('/designations/{id}', [AdminController::class, 'updateDesignation'])->name('admin.designations.update');
        Route::delete('/designations/{id}', [AdminController::class, 'destroyDesignation'])->name('admin.designations.destroy');

        // Skills CRUD
        Route::post('/skills', [AdminController::class, 'storeSkill'])->name('admin.skills.store');
        Route::delete('/skills/{id}', [AdminController::class, 'destroySkill'])->name('admin.skills.destroy');
    });

    // Employee Group
    Route::prefix('employee')->middleware('role:employee')->group(function () {
        Route::get('/', [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');
        Route::post('/clock-in', [EmployeeDashboardController::class, 'clockIn'])->name('employee.clock-in');
        Route::post('/clock-out', [EmployeeDashboardController::class, 'clockOut'])->name('employee.clock-out');
        Route::post('/tasks/{id}/complete', [EmployeeDashboardController::class, 'completeTask'])->name('employee.tasks.complete');

        // Attendance & Leaves
        Route::get('/attendance', [\App\Http\Controllers\AttendanceController::class, 'employeeIndex'])->name('employee.attendance.index');
        Route::post('/leaves', [\App\Http\Controllers\AttendanceController::class, 'storeLeaveRequest'])->name('employee.leaves.store');
    });

    // Manager Group
    Route::prefix('dashboard')->middleware('role:manager')->group(function () {
        Route::get('/', [ManagerDashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/employees', [ManagerDashboardController::class, 'employees'])->name('dashboard.employees.index');
        Route::get('/employees/search', [ManagerDashboardController::class, 'searchEmployees'])->name('dashboard.employees.search');
        Route::get('/search', [ManagerDashboardController::class, 'globalSearch'])->name('dashboard.search');
        Route::get('/search-api', [ManagerDashboardController::class, 'globalSearchApi'])->name('dashboard.search-api');
        Route::get('/employees/{id}', [ManagerDashboardController::class, 'employeeShow'])->name('dashboard.employees.show');
        Route::get('/tasks', [ManagerDashboardController::class, 'tasks'])->name('dashboard.tasks.index');
        Route::get('/reports', [ManagerDashboardController::class, 'reports'])->name('dashboard.reports.index');
        Route::post('/reports', [ManagerDashboardController::class, 'reportStore'])->name('dashboard.reports.store');
        Route::get('/reports/{id}', [ManagerDashboardController::class, 'reportShow'])->name('dashboard.reports.show');

        // Attendance & Leaves Management
        Route::get('/attendance', [\App\Http\Controllers\AttendanceController::class, 'managerIndex'])->name('dashboard.attendance.index');
        Route::post('/leaves/{id}/approve', [\App\Http\Controllers\AttendanceController::class, 'approveLeave'])->name('dashboard.leaves.approve');
        Route::post('/leaves/{id}/reject', [\App\Http\Controllers\AttendanceController::class, 'rejectLeave'])->name('dashboard.leaves.reject');

        // Task Actions
        Route::post('/tasks', [TaskManagementController::class, 'store'])->name('dashboard.tasks.store');
        Route::patch('/tasks/{id}', [TaskManagementController::class, 'update'])->name('dashboard.tasks.update');

        // Predictive Intelligence Views
        Route::get('/risks', [RiskCenterController::class, 'index'])->name('dashboard.risks.index');
        Route::get('/risks/data', [RiskCenterController::class, 'getRisksData'])->name('dashboard.risks.data');
        Route::post('/risks/{id}/resolve', [RiskCenterController::class, 'resolve'])->name('dashboard.risks.resolve');
        Route::post('/risks/{id}/notes', [RiskCenterController::class, 'saveNotes'])->name('dashboard.risks.notes');
        Route::post('/risks/{id}/follow-up', [RiskCenterController::class, 'assignFollowUp'])->name('dashboard.risks.follow-up');
        Route::get('/health', [TeamHealthController::class, 'index'])->name('dashboard.health.index');
        Route::get('/workload', [WorkloadAnalysisController::class, 'index'])->name('dashboard.workload.index');
        Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('dashboard.notifications.index');
        Route::post('/notifications/read-all', [NotificationCenterController::class, 'markAllRead'])->name('dashboard.notifications.read-all');

        // GitLab Engineering routes
        Route::get('/engineering', [ManagerDashboardController::class, 'engineeringIndex'])->name('dashboard.engineering.index');
        Route::post('/employees/{id}/map-gitlab', [ManagerDashboardController::class, 'mapGitlab'])->name('dashboard.employees.map-gitlab');
        Route::post('/employees/{id}/clock-in', [ManagerDashboardController::class, 'employeeClockIn'])->name('dashboard.employees.clock-in');
        Route::post('/employees/{id}/clock-out', [ManagerDashboardController::class, 'employeeClockOut'])->name('dashboard.employees.clock-out');
        Route::post('/employees/{id}/skills', [ManagerDashboardController::class, 'addSkill'])->name('dashboard.employees.skills.add');
        Route::delete('/employees/{id}/skills/{skillId}', [ManagerDashboardController::class, 'removeSkill'])->name('dashboard.employees.skills.remove');
        Route::post('/employees/{id}/projects', [ManagerDashboardController::class, 'allocateProject'])->name('dashboard.employees.projects.allocate');
        Route::delete('/employees/{id}/projects/{projectId}', [ManagerDashboardController::class, 'deallocateProject'])->name('dashboard.employees.projects.deallocate');
        Route::post('/merge-requests/{id}/approve', [ManagerDashboardController::class, 'approveMergeRequest'])->name('dashboard.mr.approve');
        Route::post('/merge-requests/{id}/reject', [ManagerDashboardController::class, 'rejectMergeRequest'])->name('dashboard.mr.reject');

        // Team Management Routes
        Route::get('/teams', [TeamController::class, 'index'])->name('dashboard.teams.index');
        Route::post('/teams', [TeamController::class, 'store'])->name('dashboard.teams.store');
        Route::get('/teams/{id}', [TeamController::class, 'show'])->name('dashboard.teams.show');
        Route::delete('/teams/{id}', [TeamController::class, 'destroy'])->name('dashboard.teams.destroy');
        Route::post('/teams/{teamId}/meetings', [ManagerDashboardController::class, 'meetingStore'])->name('dashboard.teams.meetings.store');
        Route::post('/teams/{teamId}/meetings/{meetingId}/notes', [TeamController::class, 'updateMeetingNotes'])->name('dashboard.teams.meetings.notes.update');
        
        // Meeting Intelligence Module Routes
        Route::get('/meetings/{id}', [ManagerDashboardController::class, 'meetingShow'])->name('dashboard.meetings.show');
        Route::post('/meetings/{id}/reschedule', [ManagerDashboardController::class, 'meetingReschedule'])->name('dashboard.meetings.reschedule');
        Route::post('/meetings/{id}/cancel', [ManagerDashboardController::class, 'meetingCancel'])->name('dashboard.meetings.cancel');
        Route::post('/meetings/{id}/complete', [ManagerDashboardController::class, 'meetingComplete'])->name('dashboard.meetings.complete');
        Route::post('/meetings/{id}/sync-fireflies', [ManagerDashboardController::class, 'syncFireflies'])->name('dashboard.meetings.sync-fireflies');
        Route::get('/fireflies-test', [ManagerDashboardController::class, 'firefliesTest'])->name('dashboard.fireflies-test');
        
        // Action Items & Decisions Routes
        Route::post('/action-items', [ManagerDashboardController::class, 'actionItemStore'])->name('dashboard.action-items.store');
        Route::post('/action-items/{id}/update', [ManagerDashboardController::class, 'actionItemUpdate'])->name('dashboard.action-items.update');
        Route::post('/action-items/{id}/delete', [ManagerDashboardController::class, 'actionItemDelete'])->name('dashboard.action-items.delete');
        Route::post('/decisions', [ManagerDashboardController::class, 'decisionStore'])->name('dashboard.decisions.store');
        Route::post('/decisions/{id}/delete', [ManagerDashboardController::class, 'decisionDelete'])->name('dashboard.decisions.delete');
        Route::post('/teams/{teamId}/tasks', [TeamController::class, 'storeTask'])->name('dashboard.teams.tasks.store');

        // Leaderboard Route
        Route::get('/leaderboard', [\App\Http\Controllers\LeaderboardController::class, 'index'])->name('dashboard.leaderboard.index');

        // Project Management Routes
        Route::get('/projects', [\App\Http\Controllers\ProjectController::class, 'index'])->name('dashboard.projects.index');
        Route::post('/projects', [\App\Http\Controllers\ProjectController::class, 'store'])->name('dashboard.projects.store');
        Route::get('/projects/{id}', [\App\Http\Controllers\ProjectController::class, 'show'])->name('dashboard.projects.show');
        Route::patch('/projects/{id}', [\App\Http\Controllers\ProjectController::class, 'update'])->name('dashboard.projects.update');
        Route::post('/projects/{id}/archive', [\App\Http\Controllers\ProjectController::class, 'toggleArchive'])->name('dashboard.projects.archive');
        Route::delete('/projects/{id}', [\App\Http\Controllers\ProjectController::class, 'destroy'])->name('dashboard.projects.destroy');
        Route::post('/projects/{projectId}/tasks', [\App\Http\Controllers\ProjectController::class, 'storeTask'])->name('dashboard.projects.tasks.store');

        // GitLab Sync Routes
        Route::post('/projects/{id}/sync-members', [\App\Http\Controllers\ProjectController::class, 'syncMembers'])->name('dashboard.projects.sync-members');
        Route::post('/projects/{id}/sync-commits', [\App\Http\Controllers\ProjectController::class, 'syncCommits'])->name('dashboard.projects.sync-commits');
        Route::post('/projects/{id}/sync-mrs', [\App\Http\Controllers\ProjectController::class, 'syncMergeRequests'])->name('dashboard.projects.sync-mrs');
        Route::post('/projects/{id}/sync-reviews', [\App\Http\Controllers\ProjectController::class, 'syncReviews'])->name('dashboard.projects.sync-reviews');

        // Dashboard Quick Actions
        Route::post('/tasks/{id}/extend-deadline', [ManagerDashboardController::class, 'extendDeadline'])->name('dashboard.tasks.extend-deadline');
        Route::post('/employees/{id}/send-reminder', [ManagerDashboardController::class, 'sendReminder'])->name('dashboard.employees.send-reminder');

        // Developer Tools
        Route::get('/developer', [DeveloperController::class, 'index'])->name('dashboard.developer.index');
        Route::post('/developer/tokens', [DeveloperController::class, 'store'])->name('dashboard.developer.tokens.store');
        Route::delete('/developer/tokens/{id}', [DeveloperController::class, 'destroy'])->name('dashboard.developer.tokens.destroy');
        Route::post('/developer/fireflies/send-test', [ManagerDashboardController::class, 'sendTestWebhook'])->name('dashboard.developer.fireflies.send-test');
    });
});
