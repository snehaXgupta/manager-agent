<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\PerformanceReport;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\PerformanceAnalyticsService;
use App\Services\OllamaAiService;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ManagerDashboardController extends Controller
{
    protected $analyticsService;

    public function __construct(PerformanceAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    protected function getActiveManager()
    {
        return auth()->user();
    }

    /**
     * Dashboard Overview (Home)
     */
    public function index()
    {
        $manager = $this->getActiveManager();
        $teamUserSubquery = User::select('id')->where('manager_id', $manager->id);

        $totalTeam = User::where('manager_id', $manager->id)->count();

        // Parse filters
        $duration = request('duration', '7_days');
        $topLimit = (int) request('top', 3);
        if (!in_array($topLimit, [3, 20, 50])) {
            $topLimit = 3;
        }

        $endDate = Carbon::now()->endOfDay();
        switch ($duration) {
            case 'today':
                $startDate = Carbon::now()->startOfDay();
                break;
            case '30_days':
                $startDate = Carbon::now()->subDays(29)->startOfDay();
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth()->startOfDay();
                break;
            case 'all_time':
                $startDate = Carbon::now()->subDays(365)->startOfDay();
                break;
            case '7_days':
            default:
                $startDate = Carbon::now()->subDays(6)->startOfDay();
                break;
        }

        // Active Today (Present or Late checked in)
        $activeToday = AttendanceLog::whereIn('user_id', $teamUserSubquery)
            ->where('date', Carbon::today()->toDateString())
            ->whereIn('status', ['present', 'late'])
            ->count();

        // Tasks breakdown scoped to selected duration range
        $tasksQuery = Task::whereIn('assigned_to', $teamUserSubquery)
            ->whereBetween('created_at', [$startDate, $endDate]);
        $tasksAssigned = (clone $tasksQuery)->count();
        $tasksCompleted = (clone $tasksQuery)->where('status', 'completed')->count();
        $tasksInProgress = (clone $tasksQuery)->where('status', 'in_progress')->count();
        $tasksPending = (clone $tasksQuery)->where('status', 'pending')->count();

        // Team Metrics
        $metrics = $this->analyticsService->calculateTeamMetrics(
            $manager->id, 
            $startDate, 
            $endDate
        );

        // Quick Health Indicator
        $score = $metrics['manager_score'] ?? 0;
        if ($score >= 80) {
            $healthStatus = 'Excellent';
            $healthClass = 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-400 border-green-200 dark:border-green-800';
        } elseif ($score >= 60) {
            $healthStatus = 'Healthy';
            $healthClass = 'bg-sky-100 text-skyAccent dark:bg-blue-950/40 dark:text-blue-400 border-sky-200 dark:border-blue-800';
        } else {
            $healthStatus = 'Needs Attention';
            $healthClass = 'bg-orange-100 text-orange-800 dark:bg-orange-950/40 dark:text-orange-400 border-orange-200 dark:border-orange-800';
        }

        // Top Performers based on completed tasks within duration
        $performers = User::where('manager_id', $manager->id)
            ->where('role', 'employee')
            ->withCount([
                'tasks' => function ($query) use ($startDate, $endDate) {
                    $query->where('status', 'completed')
                          ->whereBetween('updated_at', [$startDate, $endDate]);
                },
                'tasks as tasks_assigned_count' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            ])
            ->orderBy('tasks_count', 'desc')
            ->take($topLimit)
            ->get();

        // Period-based attendance breakdown
        $attendanceStats = AttendanceLog::whereIn('user_id', $teamUserSubquery)
            ->whereBetween('date', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $presentCount = $attendanceStats['present'] ?? 0;
        $lateCount = $attendanceStats['late'] ?? 0;
        $absentCountRange = $duration === 'today' 
            ? max(0, $totalTeam - ($presentCount + $lateCount)) 
            : ($attendanceStats['absent'] ?? 0);

        // Actions required calculations:
        // 1. Absent Employees: limit to 5 in UI but get total count
        $absentCount = User::where('manager_id', $manager->id)
            ->where('role', 'employee')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)
                    ->from('attendance_logs')
                    ->whereColumn('attendance_logs.user_id', 'users.id')
                    ->where('attendance_logs.date', Carbon::today()->toDateString())
                    ->whereIn('attendance_logs.status', ['present', 'late']);
            })
            ->count();

        $absentEmployees = User::where('manager_id', $manager->id)
            ->where('role', 'employee')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)
                    ->from('attendance_logs')
                    ->whereColumn('attendance_logs.user_id', 'users.id')
                    ->where('attendance_logs.date', Carbon::today()->toDateString())
                    ->whereIn('attendance_logs.status', ['present', 'late']);
            })
            ->take(5)
            ->get();

        // 2. Overdue tasks / Missed deadlines: limit to 5 in UI but get total count
        $overdueQuery = Task::whereIn('assigned_to', $teamUserSubquery)
            ->where('status', '!=', 'completed')
            ->where('deadline', '<', Carbon::now());

        $overdueCount = $overdueQuery->count();
        $overdueTasks = $overdueQuery->with('assignee')->take(5)->get();

        // 3. Meeting Intelligence home widgets data
        $upcomingMeetings = \App\Models\Meeting::where('manager_id', $manager->id)
            ->where('status', 'Scheduled')
            ->with('team')
            ->orderBy('meeting_date', 'asc')
            ->orderBy('meeting_time', 'asc')
            ->take(5)
            ->get();

        $pendingActionItems = \App\Models\MeetingActionItem::whereHas('meeting', function ($q) use ($manager) {
                $q->where('manager_id', $manager->id);
            })
            ->where('status', '!=', 'Completed')
            ->with(['assignee', 'meeting.team'])
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        $recentMeetingSummaries = \App\Models\Meeting::where('manager_id', $manager->id)
            ->where('status', 'Completed')
            ->whereHas('transcript')
            ->with(['transcript', 'team'])
            ->orderBy('meeting_date', 'desc')
            ->orderBy('meeting_time', 'desc')
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'manager',
            'totalTeam',
            'activeToday',
            'tasksAssigned',
            'tasksCompleted',
            'tasksInProgress',
            'tasksPending',
            'metrics',
            'healthStatus',
            'healthClass',
            'performers',
            'absentCount',
            'absentEmployees',
            'overdueCount',
            'overdueTasks',
            'upcomingMeetings',
            'pendingActionItems',
            'recentMeetingSummaries',
            'presentCount',
            'lateCount',
            'absentCountRange',
            'topLimit',
            'duration'
        ));
    }

    /**
     * List Team Employees and Live Statuses
     */
    public function employees(Request $request)
    {
        $manager = $this->getActiveManager();
        
        $query = User::where('manager_id', $manager->id)
            ->where('role', 'employee')
            ->with(['activeTimeEntry.task', 'department', 'designation']);

        // 1. Search Query (name or email)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 2. Attendance Status today filter
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'present' || $status === 'late') {
                $query->whereExists(function($q) use ($status) {
                    $q->selectRaw(1)
                      ->from('attendance_logs')
                      ->whereColumn('attendance_logs.user_id', 'users.id')
                      ->where('attendance_logs.date', Carbon::today()->toDateString())
                      ->where('attendance_logs.status', $status);
                });
            } elseif ($status === 'absent') {
                $query->whereNotExists(function($q) {
                    $q->selectRaw(1)
                      ->from('attendance_logs')
                      ->whereColumn('attendance_logs.user_id', 'users.id')
                      ->where('attendance_logs.date', Carbon::today()->toDateString())
                      ->whereIn('attendance_logs.status', ['present', 'late']);
                });
            }
        }

        // 3. Active Timer vs Idle filter
        if ($request->filled('timer')) {
            $timer = $request->timer;
            if ($timer === 'active') {
                $query->whereExists(function($q) {
                    $q->selectRaw(1)
                      ->from('time_entries')
                      ->whereColumn('time_entries.user_id', 'users.id')
                      ->whereNull('time_entries.stopped_at');
                });
            } elseif ($timer === 'idle') {
                $query->whereNotExists(function($q) {
                    $q->selectRaw(1)
                      ->from('time_entries')
                      ->whereColumn('time_entries.user_id', 'users.id')
                      ->whereNull('time_entries.stopped_at');
                });
            }
        }

        // Department filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Designation filter
        if ($request->filled('designation_id')) {
            $query->where('designation_id', $request->designation_id);
        }

        // 4. Sorting by name
        $sortBy = $request->query('sort_by', 'name_asc');
        if ($sortBy === 'name_desc') {
            $query->orderBy('name', 'desc');
        } else {
            $query->orderBy('name', 'asc');
        }

        // Paginate results (maintains performance)
        $employees = $query->paginate(15)->withQueryString();

        // Fetch today's aggregated time worked in bulk for the current page users
        $timeWorkedToday = TimeEntry::whereIn('user_id', $employees->pluck('id'))
            ->whereDate('started_at', Carbon::today())
            ->groupBy('user_id')
            ->select('user_id')
            ->selectRaw('SUM(duration_seconds) as total_seconds')
            ->pluck('total_seconds', 'user_id');

        // Fetch today's completed tasks count in bulk for the current page users
        $completedTodayCounts = Task::whereIn('assigned_to', $employees->pluck('id'))
            ->where('status', 'completed')
            ->whereDate('updated_at', Carbon::today())
            ->groupBy('assigned_to')
            ->select('assigned_to')
            ->selectRaw('COUNT(*) as total_count')
            ->pluck('total_count', 'assigned_to');

        // Fetch today's attendance logs in bulk for the current page users
        $attendances = AttendanceLog::whereIn('user_id', $employees->pluck('id'))
            ->where('date', Carbon::today()->toDateString())
            ->get()
            ->keyBy('user_id');

        foreach ($employees as $employee) {
            $secondsToday = $timeWorkedToday->get($employee->id, 0);
            $completedToday = $completedTodayCounts->get($employee->id, 0);
            $attendance = $attendances->get($employee->id);

            $employee->active_timer = $employee->activeTimeEntry;
            $employee->hours_worked_today = round($secondsToday / 3600, 1);
            $employee->tasks_completed_today = $completedToday;
            $employee->attendance_status = $attendance ? $attendance->status : 'absent';
            $employee->last_active = $employee->activeTimeEntry 
                ? 'Active now' 
                : 'Never';
        }

        // Extra sorting in memory if requested for hour / task count:
        if ($sortBy === 'hours_desc') {
            $sortedItems = $employees->getCollection()->sortByDesc('hours_worked_today')->values();
            $employees->setCollection($sortedItems);
        } elseif ($sortBy === 'hours_asc') {
            $sortedItems = $employees->getCollection()->sortBy('hours_worked_today')->values();
            $employees->setCollection($sortedItems);
        } elseif ($sortBy === 'tasks_desc') {
            $sortedItems = $employees->getCollection()->sortByDesc('tasks_completed_today')->values();
            $employees->setCollection($sortedItems);
        }

        $departments = \App\Models\Department::orderBy('name', 'asc')->get();
        $designations = \App\Models\Designation::orderBy('name', 'asc')->get();

        return view('dashboard.employees.index', compact('employees', 'departments', 'designations'));
    }

    /**
     * Individual Employee Detail and Metrics
     */
    public function employeeShow($id)
    {
        $manager = $this->getActiveManager();
        $employee = User::where('id', $id)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        // Calculate user performance metrics
        $weeklyMetrics = $this->analyticsService->calculateUserMetrics(
            $employee->id, 
            Carbon::now()->subDays(6)->startOfDay(), 
            Carbon::now()->endOfDay()
        );

        $monthlyMetrics = $this->analyticsService->calculateUserMetrics(
            $employee->id, 
            Carbon::now()->subDays(29)->startOfDay(), 
            Carbon::now()->endOfDay()
        );

        // Fetch employee tasks
        $tasks = Task::where('assigned_to', $employee->id)
            ->orderByRaw("CASE WHEN status = 'completed' THEN 2 ELSE 1 END")
            ->orderBy('deadline', 'asc')
            ->get();

        // Generate AI employee insights
        $aiService = app(OllamaAiService::class);
        $insights = \Illuminate\Support\Facades\Cache::remember("employee_insights_{$employee->id}", 600, function () use ($aiService, $employee, $weeklyMetrics) {
            return $aiService->generateEmployeeInsights($employee, $weeklyMetrics);
        });

        // GitLab Engineering Metrics calculation
        $engineeringMetrics = [
            'projects_assigned' => $employee->projects()->count(),
            'repos_accessed' => \App\Models\Repository::whereIn('project_id', $employee->projects()->pluck('projects.id'))->count(),
            'commits_count' => \App\Models\Commit::where('employee_id', $employee->id)->count(),
            'open_mrs' => \App\Models\MergeRequest::where('employee_id', $employee->id)->where('status', 'Opened')->count(),
            'merged_mrs' => \App\Models\MergeRequest::where('employee_id', $employee->id)->where('status', 'Merged')->count(),
            'reviews_count' => \App\Models\Review::where('reviewer_id', $employee->id)->count(),
            'approvals_count' => \App\Models\Approval::where('approved_by', $employee->id)->count(),
        ];

        // GitLab activity timeline
        $commitsTimeline = \App\Models\Commit::where('employee_id', $employee->id)
            ->with('repository')
            ->orderBy('committed_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn($c) => [
                'type' => 'commit',
                'title' => "Pushed commit '{$c->message}'",
                'meta' => "SHA: " . substr($c->commit_sha, 0, 8) . " | Branch: {$c->branch} | Repo: {$c->repository->repository_name}",
                'time' => $c->committed_at,
            ]);

        $mrsTimeline = \App\Models\MergeRequest::where('employee_id', $employee->id)
            ->with('repository')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn($m) => [
                'type' => 'mr',
                'title' => "Created Merge Request '{$m->title}'",
                'meta' => "Status: {$m->status} | Repo: {$m->repository->repository_name}",
                'time' => $m->created_at,
            ]);

        $engineeringTimeline = $commitsTimeline->concat($mrsTimeline)
            ->sortByDesc('time')
            ->take(15)
            ->values();

        // Mapped lookup data
        $allSkills = \App\Models\Skill::orderBy('name', 'asc')->get();
        $allProjects = \App\Models\Project::orderBy('name', 'asc')->get();

        // Calculate dynamic weekly performance history for line charts
        $performanceHistory = [];
        for ($i = 3; $i >= 0; $i--) {
            $start = Carbon::now()->subWeeks($i)->startOfWeek();
            $end = Carbon::now()->subWeeks($i)->endOfWeek();
            $label = "Wk -" . $i . " (" . $start->format('M d') . ")";
            
            $userMetrics = $this->analyticsService->calculateUserMetrics($employee->id, $start, $end);
            $performanceHistory[] = [
                'label' => $label,
                'developer_score' => $userMetrics['developer_score'] ?? 0,
                'task_completion_rate' => $userMetrics['task_completion_rate'] ?? 0,
                'deadline_adherence_rate' => $userMetrics['deadline_adherence_rate'] ?? 0,
                'productivity_score' => $userMetrics['productivity_score'] ?? 0,
            ];
        }

        $todayAttendance = \App\Models\AttendanceLog::where('user_id', $employee->id)
            ->whereDate('date', Carbon::today()->toDateString())
            ->first();

        return view('dashboard.employees.show', compact(
            'employee', 
            'weeklyMetrics', 
            'monthlyMetrics', 
            'tasks', 
            'insights',
            'engineeringMetrics',
            'engineeringTimeline',
            'allSkills',
            'allProjects',
            'performanceHistory',
            'todayAttendance'
        ));
    }

    public function addSkill(Request $request, $id)
    {
        $manager = $this->getActiveManager();
        $employee = User::where('id', $id)->where('manager_id', $manager->id)->firstOrFail();
        
        $request->validate([
            'skill_id' => 'required|exists:skills,id',
            'proficiency' => 'required|integer|min:1|max:5'
        ]);

        $employee->skills()->syncWithoutDetaching([
            $request->skill_id => ['proficiency' => $request->proficiency]
        ]);

        $employee->skills()->updateExistingPivot($request->skill_id, [
            'proficiency' => $request->proficiency
        ]);

        return redirect()->back()->with('success', 'Skill assigned successfully.');
    }

    public function removeSkill($id, $skillId)
    {
        $manager = $this->getActiveManager();
        $employee = User::where('id', $id)->where('manager_id', $manager->id)->firstOrFail();
        
        $employee->skills()->detach($skillId);

        return redirect()->back()->with('success', 'Skill removed successfully.');
    }

    public function allocateProject(Request $request, $id)
    {
        $manager = $this->getActiveManager();
        $employee = User::where('id', $id)->where('manager_id', $manager->id)->firstOrFail();
        
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'gitlab_member_id' => 'nullable|string'
        ]);

        $employee->projects()->syncWithoutDetaching([
            $request->project_id => ['gitlab_member_id' => $request->gitlab_member_id]
        ]);

        return redirect()->back()->with('success', 'Project allocated successfully.');
    }

    public function deallocateProject($id, $projectId)
    {
        $manager = $this->getActiveManager();
        $employee = User::where('id', $id)->where('manager_id', $manager->id)->firstOrFail();
        
        $employee->projects()->detach($projectId);

        return redirect()->back()->with('success', 'Project allocation removed.');
    }

    /**
     * Extend a task deadline by 2 days as a quick action.
     */
    public function extendDeadline(Request $request, $id)
    {
        $manager = $this->getActiveManager();
        
        $task = Task::where('id', $id)
            ->whereIn('assigned_to', function ($query) use ($manager) {
                $query->select('id')->from('users')->where('manager_id', $manager->id);
            })
            ->firstOrFail();

        $currentDeadline = $task->deadline ? Carbon::parse($task->deadline) : Carbon::now();
        $newDeadline = $currentDeadline->isPast() ? Carbon::now()->addDays(2) : $currentDeadline->addDays(2);
        
        $task->update([
            'deadline' => $newDeadline
        ]);

        return redirect()->back()->with('success', "Deadline for task '{$task->title}' extended by 2 days.");
    }

    /**
     * Send attendance reminder notification to an absent employee.
     */
    public function sendReminder(Request $request, $id)
    {
        $manager = $this->getActiveManager();
        $employee = User::where('id', $id)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        Notification::create([
            'user_id' => $employee->id,
            'type' => 'attendance_reminder',
            'severity' => 'WARNING',
            'title' => 'Attendance Action Required',
            'message' => "Your manager {$manager->name} sent a reminder regarding today's attendance check-in.",
            'is_read' => false,
        ]);

        return redirect()->back()->with('success', "Attendance check-in reminder sent to {$employee->name}.");
    }

    public function tasks(Request $request)
    {
        $manager = $this->getActiveManager();
        $teamUserSubquery = function ($query) use ($manager) {
            $query->select('id')->from('users')->where('manager_id', $manager->id);
        };

        $query = Task::whereIn('assigned_to', $teamUserSubquery)
            ->with('assignee');

        // 1. Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 2. Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Health filter
        if ($request->filled('health')) {
            $health = $request->health;
            if ($health === 'overdue') {
                $query->where('status', '!=', 'completed')
                      ->where('deadline', '<', Carbon::now());
            } elseif ($health === 'approaching') {
                $query->where('status', '!=', 'completed')
                      ->where('deadline', '>=', Carbon::now())
                      ->where('deadline', '<=', Carbon::now()->addHours(48));
            } elseif ($health === 'on_track') {
                $query->where(function($q) {
                    $q->where('status', 'completed')
                      ->orWhereNull('deadline')
                      ->orWhere('deadline', '>', Carbon::now()->addHours(48));
                });
            }
        }

        // 4. Sorting
        $sortBy = $request->query('sort_by', 'deadline_asc');
        if ($sortBy === 'deadline_desc') {
            $query->orderBy('deadline', 'desc');
        } elseif ($sortBy === 'title_asc') {
            $query->orderBy('title', 'asc');
        } elseif ($sortBy === 'status') {
            $query->orderBy('status', 'asc');
        } else {
            $query->orderByRaw("CASE WHEN status = 'completed' THEN 2 ELSE 1 END")
                  ->orderByRaw("CASE WHEN deadline IS NULL THEN 1 ELSE 0 END")
                  ->orderBy('deadline', 'asc');
        }

        $tasks = $query->paginate(15)->withQueryString();

        // Limit assignee dropdown options to first 100 to avoid DOM bloating
        $employees = User::where('manager_id', $manager->id)->take(100)->get();

        return view('dashboard.tasks.index', compact('tasks', 'employees'));
    }

    /**
     * Archived reports listing
     */
    public function reports(Request $request)
    {
        $manager = $this->getActiveManager();
        
        $query = PerformanceReport::where('manager_id', $manager->id);

        if ($request->filled('type')) {
            $query->where('report_type', $request->type);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('generated_at', '>=', Carbon::parse($request->start_date));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('generated_at', '<=', Carbon::parse($request->end_date));
        }

        $reports = $query->orderBy('generated_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $projects = \App\Models\Project::where('manager_id', $manager->id)
            ->where('is_archived', false)
            ->get();

        return view('dashboard.reports.index', compact('reports', 'projects'));
    }

    /**
     * Show a detailed page for a weekly/monthly performance report.
     */
    public function reportShow($id)
    {
        $manager = $this->getActiveManager();
        $report = PerformanceReport::where('id', $id)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        // Run previous period comparisons
        $reportService = app(\App\Services\ReportService::class);
        $comparison = $reportService->compareWithPrevious($report);

        return view('dashboard.reports.show', compact('report', 'comparison'));
    }

    /**
     * Generate and persist a new performance report on demand.
     */
    public function reportStore(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:daily,weekly,monthly,project_completion,delayed_projects,team_wise_projects',
            'project_id' => 'required_if:report_type,project_completion|nullable|exists:projects,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $manager = $this->getActiveManager();
        $reportService = app(\App\Services\ReportService::class);

        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : null;
        $projectId = $request->filled('project_id') ? (int) $request->project_id : null;

        try {
            $report = $reportService->generateReport($manager->id, $request->report_type, $startDate, $endDate, $projectId);
            return redirect()->route('dashboard.reports.show', $report->id)->with('success', 'New performance report generated and saved successfully!');
        } catch (\Exception $e) {
            $errorMessage = substr($e->getMessage(), 0, 500);
            return redirect()->back()->with('error', 'Error generating report: ' . $errorMessage);
        }
    }

    /**
     * Search employees dynamically via AJAX.
     */
    public function searchEmployees(Request $request)
    {
        $manager = $this->getActiveManager();
        $query = $request->query('query', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $employees = User::where('manager_id', $manager->id)
            ->where('role', 'employee')
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->take(20)
            ->get(['id', 'name', 'email']);

        return response()->json($employees);
    }

    /**
     * Map employee profile to a verified GitLab account.
     */
    public function mapGitlab(Request $request, $id, \App\Services\GitlabMemberService $memberService)
    {
        $employee = User::where('id', $id)
            ->where('manager_id', auth()->id())
            ->firstOrFail();

        $request->validate([
            'gitlab_username_or_email' => 'required|string|max:255',
        ]);

        $queryVal = $request->gitlab_username_or_email;

        // 1. Verify user exists in GitLab
        $gitlabUser = $memberService->verifyUser($queryVal);

        if (!$gitlabUser) {
            return redirect()->back()->with('error', "Could not find a valid GitLab account for '{$queryVal}'.");
        }

        // 2. Prevent duplicate mapping
        $duplicate = User::where('gitlab_user_id', $gitlabUser['id'])
            ->where('id', '!=', $employee->id)
            ->first();

        if ($duplicate) {
            return redirect()->back()->with('error', "This GitLab account is already mapped to another employee ({$duplicate->name}).");
        }

        // 3. Update profile fields
        $employee->update([
            'gitlab_user_id' => $gitlabUser['id'],
            'gitlab_username' => $gitlabUser['username'],
            'gitlab_email' => $gitlabUser['email'],
        ]);

        // Auto-add employee to repository members of projects they are assigned to
        $employee->load('projects');
        foreach ($employee->projects as $project) {
            if ($project->repository) {
                $memberService->addMemberToRepository($project, $employee);
            }
        }

        return redirect()->back()->with('success', "Successfully mapped {$employee->name} to GitLab account: {$gitlabUser['username']}.");
    }

    /**
     * Approve a Merge Request.
     */
    public function approveMergeRequest($id, \App\Services\GitlabMergeRequestService $mrService)
    {
        $manager = $this->getActiveManager();
        $mr = \App\Models\MergeRequest::where('id', $id)->firstOrFail();

        // Ensure the project belongs to this manager
        if ($mr->project->manager_id !== $manager->id) {
            abort(403, 'Unauthorized action.');
        }

        $mrService->approveMergeRequest($mr, $manager);

        return redirect()->back()->with('success', "Merge Request '{$mr->title}' approved successfully.");
    }

    /**
     * Reject a Merge Request.
     */
    public function rejectMergeRequest($id, \App\Services\GitlabMergeRequestService $mrService)
    {
        $manager = $this->getActiveManager();
        $mr = \App\Models\MergeRequest::where('id', $id)->firstOrFail();

        // Ensure the project belongs to this manager
        if ($mr->project->manager_id !== $manager->id) {
            abort(403, 'Unauthorized action.');
        }

        $mrService->rejectMergeRequest($mr, $manager);

        return redirect()->back()->with('success', "Merge Request '{$mr->title}' rejected.");
    }

    /**
     * Display the GitLab-integrated Engineering Dashboard.
     */
    public function engineeringIndex()
    {
        $manager = $this->getActiveManager();

        // Get all projects under this manager
        $projects = \App\Models\Project::where('manager_id', $manager->id)->get();
        $projectIds = $projects->pluck('id');

        // Total Projects Count
        $totalProjects = $projects->count();

        // Repositories Count
        $repositories = \App\Models\Repository::whereIn('project_id', $projectIds)->get();
        $repoIds = $repositories->pluck('id');
        $totalRepositories = $repositories->count();

        // Commits Count
        $commitsQuery = \App\Models\Commit::whereIn('project_id', $projectIds);
        $totalCommits = $commitsQuery->count();

        // Merge Requests Counts
        $mrsQuery = \App\Models\MergeRequest::whereIn('project_id', $projectIds);
        $totalOpenMRs = (clone $mrsQuery)->where('status', 'Opened')->count();
        $totalMergedMRs = (clone $mrsQuery)->where('status', 'Merged')->count();

        // Reviews Count
        $totalPendingReviews = \App\Models\Review::whereIn('merge_request_id', function ($q) use ($projectIds) {
            $q->select('id')->from('merge_requests')->whereIn('project_id', $projectIds);
        })->where('status', 'Commented')->count();

        // Approvals Count
        $totalApprovals = \App\Models\Approval::whereIn('merge_request_id', function ($q) use ($projectIds) {
            $q->select('id')->from('merge_requests')->whereIn('project_id', $projectIds);
        })->count();

        // Widgets array
        $widgets = [
            'total_projects' => $totalProjects,
            'total_repositories' => $totalRepositories,
            'total_commits' => $totalCommits,
            'open_mrs' => $totalOpenMRs,
            'merged_mrs' => $totalMergedMRs,
            'pending_reviews' => $totalPendingReviews,
            'approvals' => $totalApprovals,
        ];

        // 1. Commits Per Day (Last 7 Days)
        $commitsPerDay = \App\Models\Commit::whereIn('project_id', $projectIds)
            ->where('committed_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(committed_at) as commit_date, COUNT(*) as commit_count')
            ->groupBy('commit_date')
            ->orderBy('commit_date', 'asc')
            ->pluck('commit_count', 'commit_date')
            ->toArray();

        // Ensure all last 7 days have a count
        $commitsChartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $commitsChartData[$date] = $commitsPerDay[$date] ?? 0;
        }

        // 2. MR Status Distribution
        $mrStatusCounts = \App\Models\MergeRequest::whereIn('project_id', $projectIds)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $mrDistribution = [
            'Opened' => $mrStatusCounts['Opened'] ?? 0,
            'Approved' => $mrStatusCounts['Approved'] ?? 0,
            'Rejected' => $mrStatusCounts['Rejected'] ?? 0,
            'Merged' => $mrStatusCounts['Merged'] ?? 0,
        ];

        // 3. Employee Contribution Trend (Top 5 contributors)
        $employeeContributions = \App\Models\Commit::whereIn('project_id', $projectIds)
            ->join('users', 'commits.employee_id', '=', 'users.id')
            ->selectRaw('users.name as employee_name, COUNT(*) as commit_count')
            ->groupBy('employee_name')
            ->orderBy('commit_count', 'desc')
            ->take(5)
            ->pluck('commit_count', 'employee_name')
            ->toArray();

        // 4. Pending Merge Requests list
        $pendingMergeRequests = \App\Models\MergeRequest::whereIn('project_id', $projectIds)
            ->where('status', 'Opened')
            ->with(['employee', 'repository'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($mr) {
                // Mock files changed statistics
                $mr->files_changed_count = rand(2, 8);
                return $mr;
            });

        // 5. Recent Activity Feed (Commits, MR changes, approvals)
        $recentCommits = \App\Models\Commit::whereIn('project_id', $projectIds)
            ->with(['employee', 'repository'])
            ->orderBy('committed_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn($c) => [
                'type' => 'commit',
                'title' => "Commit '{$c->message}' pushed",
                'user' => $c->employee->name,
                'meta' => "SHA: " . substr($c->commit_sha, 0, 8) . " | Branch: {$c->branch}",
                'time' => $c->committed_at,
            ]);

        $recentMRs = \App\Models\MergeRequest::whereIn('project_id', $projectIds)
            ->with(['employee', 'repository'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn($m) => [
                'type' => 'mr_opened',
                'title' => "Merge Request '{$m->title}' " . strtolower($m->status),
                'user' => $m->employee->name,
                'meta' => "Branch: {$m->source_branch} -> {$m->target_branch}",
                'time' => $m->created_at,
            ]);

        $activityFeed = $recentCommits->concat($recentMRs)
            ->sortByDesc('time')
            ->take(10)
            ->values();

        return view('dashboard.engineering', compact(
            'widgets',
            'commitsChartData',
            'mrDistribution',
            'employeeContributions',
            'pendingMergeRequests',
            'activityFeed'
        ));
    }

    /**
     * Show detailed page for a meeting.
     */
    public function meetingShow($id)
    {
        $meeting = \App\Models\Meeting::with([
            'team.members',
            'transcript',
            'actionItems.assignee',
            'decisions',
            'creator',
            'meetingParticipants'
        ])->findOrFail($id);

        if ($meeting->manager_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $error = null;
        if (!$meeting->transcript || empty($meeting->transcript->transcript)) {
            $error = 'No transcript received from Fireflies.';
        }

        return view('dashboard.meetings.show', compact('meeting', 'error'));
    }

    /**
     * Schedule a new team meeting.
     */
    public function meetingStore(Request $request, $teamId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'required|date',
            'meeting_time' => 'required',
            'duration' => 'required|integer|min:5',
            'meeting_link' => 'nullable|url',
        ]);

        $meeting = \App\Models\Meeting::create([
            'title' => $request->title,
            'description' => $request->description,
            'meeting_date' => $request->meeting_date,
            'meeting_time' => $request->meeting_time,
            'duration' => $request->duration,
            'meeting_link' => $request->meeting_link,
            'status' => 'Scheduled',
            'team_id' => $teamId,
            'manager_id' => auth()->id(),
            'created_by' => auth()->id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'meeting' => $meeting]);
        }

        return redirect()->back()->with('success', 'Meeting scheduled successfully!');
    }

    /**
     * Reschedule an existing meeting.
     */
    public function meetingReschedule(Request $request, $id)
    {
        $meeting = \App\Models\Meeting::findOrFail($id);
        if ($meeting->manager_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'meeting_date' => 'required|date',
            'meeting_time' => 'required',
        ]);

        $meeting->update([
            'meeting_date' => $request->meeting_date,
            'meeting_time' => $request->meeting_time,
            'status' => 'Scheduled',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'meeting' => $meeting]);
        }

        return redirect()->back()->with('success', 'Meeting rescheduled successfully!');
    }

    /**
     * Cancel a meeting.
     */
    public function meetingCancel(Request $request, $id)
    {
        $meeting = \App\Models\Meeting::findOrFail($id);
        if ($meeting->manager_id !== auth()->id()) {
            abort(403);
        }

        $meeting->update(['status' => 'Cancelled']);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'meeting' => $meeting]);
        }

        return redirect()->back()->with('success', 'Meeting cancelled successfully!');
    }

    /**
     * Complete a meeting.
     */
    public function meetingComplete(Request $request, $id)
    {
        $meeting = \App\Models\Meeting::findOrFail($id);
        if ($meeting->manager_id !== auth()->id()) {
            abort(403);
        }

        $meeting->update(['status' => 'Completed']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true, 
                'meeting' => $meeting->load(['transcript', 'actionItems.assignee', 'decisions'])
            ]);
        }

        return redirect()->back()->with('success', 'Meeting completed successfully!');
    }

    /**
     * Manually trigger Fireflies sync (Disabled in Webhook-only Mode).
     */
    public function syncFireflies(Request $request, $id)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false, 
                'message' => 'GraphQL API pulling is disabled. Please configure webhooks to receive transcript updates automatically.'
            ], 400);
        }

        return redirect()->back()->with('error', 'GraphQL API pulling is disabled. Please configure webhooks to receive transcript updates automatically.');
    }

    /**
     * Store a new action item.
     */
    public function actionItemStore(Request $request)
    {
        $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'assigned_to' => 'nullable|exists:users,id',
            'action_item' => 'required|string',
            'due_date' => 'nullable|date',
            'priority' => 'required|string|in:High,Medium,Low',
        ]);

        $meeting = \App\Models\Meeting::findOrFail($request->meeting_id);
        if ($meeting->manager_id !== auth()->id()) {
            abort(403);
        }

        $item = \App\Models\MeetingActionItem::create([
            'meeting_id' => $request->meeting_id,
            'assigned_to' => $request->assigned_to ?: null,
            'action_item' => $request->action_item,
            'due_date' => $request->due_date ?: null,
            'priority' => $request->priority,
            'status' => 'Pending',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'action_item' => $item->load('assignee')]);
        }

        return redirect()->back()->with('success', 'Action item created successfully!');
    }

    /**
     * Update an action item.
     */
    public function actionItemUpdate(Request $request, $id)
    {
        $item = \App\Models\MeetingActionItem::findOrFail($id);
        $meeting = $item->meeting;
        if ($meeting->manager_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|string|in:Pending,In Progress,Completed',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'nullable|string|in:High,Medium,Low',
            'due_date' => 'nullable|date',
        ]);

        $item->update($request->only(['status', 'assigned_to', 'priority', 'due_date']));

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'action_item' => $item->load('assignee')]);
        }

        return redirect()->back()->with('success', 'Action item updated successfully!');
    }

    /**
     * Delete an action item.
     */
    public function actionItemDelete(Request $request, $id)
    {
        $item = \App\Models\MeetingActionItem::findOrFail($id);
        $meeting = $item->meeting;
        if ($meeting->manager_id !== auth()->id()) {
            abort(403);
        }

        $item->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Action item deleted successfully!');
    }

    /**
     * Store a new decision.
     */
    public function decisionStore(Request $request)
    {
        $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'decision_text' => 'required|string',
        ]);

        $meeting = \App\Models\Meeting::findOrFail($request->meeting_id);
        if ($meeting->manager_id !== auth()->id()) {
            abort(403);
        }

        $decision = \App\Models\MeetingDecision::create([
            'meeting_id' => $request->meeting_id,
            'decision_text' => $request->decision_text,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'decision' => $decision]);
        }

        return redirect()->back()->with('success', 'Decision recorded successfully!');
    }

    /**
     * Delete a decision.
     */
    public function decisionDelete(Request $request, $id)
    {
        $decision = \App\Models\MeetingDecision::findOrFail($id);
        $meeting = $decision->meeting;
        if ($meeting->manager_id !== auth()->id()) {
            abort(403);
        }

        $decision->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Decision deleted successfully!');
    }

    /**
     * Show Fireflies Connection Test Page.
     */
    public function firefliesTest()
    {
        $webhookUrl = str_replace('http://', 'https://', route('api.webhooks.fireflies'));
        $webhookSecret = config('services.fireflies.webhook_secret');
        $lastWebhookReceived = \Illuminate\Support\Facades\Cache::get('fireflies_last_webhook_received_at', 'Never');
        $lastMeetingSynced = \Illuminate\Support\Facades\Cache::get('fireflies_last_meeting_synced_title', 'None');
        $lastTranscriptSynced = \Illuminate\Support\Facades\Cache::get('fireflies_last_transcript_synced_id', 'None');
        $webhookStatus = \Illuminate\Support\Facades\Cache::get('fireflies_webhook_status', 'Pending');

        $webhookPayloads = \App\Models\FirefliesWebhookPayload::orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return view('dashboard.developer.fireflies_test', compact(
            'webhookUrl',
            'webhookSecret',
            'lastWebhookReceived',
            'lastMeetingSynced',
            'lastTranscriptSynced',
            'webhookStatus',
            'webhookPayloads'
        ));
    }

    public function sendTestWebhook(Request $request)
    {
        $secret = config('services.fireflies.webhook_secret');
        if (empty($secret)) {
            return redirect()->back()->with('error', 'Fireflies webhook secret is not configured.');
        }

        $payload = [
            'meetingId' => 'test-meeting-' . \Illuminate\Support\Str::random(6),
            'title' => 'Weekly Team Sync (Test Webhook)',
            'date' => now()->toISOString(),
            'duration' => 45,
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            'transcript_text' => "Presenter: Welcome everyone to our weekly sync.\nRahul: I've completed the refactoring of the GitLab integration.\nSarah: Great. Let's make sure we write automated tests for it.",
            'summary' => [
                'overview' => 'Weekly status updates on the project integration.',
                'action_items' => [
                    'Write automated tests for the webhook receiver',
                    'Deploy the meeting tracking module to staging'
                ],
                'shorthand_bullet_points' => [
                    'Refactored the GitLab integration successfully',
                    'Decided to use a mock payload for webhook verification'
                ]
            ],
            'participants' => [
                'sarah.manager@example.com',
                'rahul.employee@example.com'
            ],
            'meeting_attendees' => [
                [
                    'displayName' => 'Sarah Manager',
                    'email' => 'sarah.manager@example.com'
                ],
                [
                    'displayName' => 'Rahul Employee',
                    'email' => 'rahul.employee@example.com'
                ]
            ]
        ];

        $jsonPayload = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $secret);

        // Dispatch request internally using Laravel's kernel handle
        $internalRequest = \Illuminate\Http\Request::create(
            route('api.webhooks.fireflies'),
            'POST',
            [],
            [],
            [],
            [
                'HTTP_X-Hub-Signature-256' => $signature,
                'CONTENT_TYPE' => 'application/json'
            ],
            $jsonPayload
        );

        try {
            $response = app()->handle($internalRequest);

            if ($response->isSuccessful()) {
                return redirect()->back()->with('success', 'Test webhook sent and processed successfully!');
            }

            $body = json_decode($response->getContent(), true);
            $msg = $body['message'] ?? 'Unknown error';
            return redirect()->back()->with('error', 'Test webhook failed: ' . $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Test webhook failed: ' . $e->getMessage());
        }
    }

    /**
     * Display global search results across multiple modules.
     */
    public function globalSearch(Request $request)
    {
        $manager = $this->getActiveManager();
        $query = $request->query('query', '');

        if (empty($query)) {
            $employees = collect();
            $teams = collect();
            $projects = collect();
            $tasks = collect();
        } else {
            $employees = User::where('manager_id', $manager->id)
                ->where('role', 'employee')
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->take(15)
                ->get();

            $teams = \App\Models\Team::where('manager_id', $manager->id)
                ->where('name', 'like', "%{$query}%")
                ->withCount('members')
                ->take(15)
                ->get();

            $projects = \App\Models\Project::where('manager_id', $manager->id)
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%");
                })
                ->withCount('members')
                ->take(15)
                ->get();

            $teamUserSubquery = User::select('id')->where('manager_id', $manager->id);
            $tasks = Task::whereIn('assigned_to', $teamUserSubquery)
                ->where(function($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%");
                })
                ->with('assignee')
                ->take(15)
                ->get();
        }

        return view('dashboard.search', compact('employees', 'teams', 'projects', 'tasks', 'query'));
    }

    /**
     * Return JSON suggestion matches for the top search bar.
     */
    public function globalSearchApi(Request $request)
    {
        $manager = $this->getActiveManager();
        $query = $request->query('query', '');

        if (strlen($query) < 2) {
            return response()->json([
                'employees' => [],
                'teams' => [],
                'projects' => [],
                'tasks' => []
            ]);
        }

        $employees = User::where('manager_id', $manager->id)
            ->where('role', 'employee')
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'name', 'email']);

        $teams = \App\Models\Team::where('manager_id', $manager->id)
            ->where('name', 'like', "%{$query}%")
            ->take(5)
            ->get(['id', 'name']);

        $projects = \App\Models\Project::where('manager_id', $manager->id)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'name', 'description']);

        $teamUserSubquery = User::select('id')->where('manager_id', $manager->id);
        $tasks = Task::whereIn('assigned_to', $teamUserSubquery)
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'title', 'status']);

        return response()->json([
            'employees' => $employees,
            'teams' => $teams,
            'projects' => $projects,
            'tasks' => $tasks
        ]);
    }

    /**
     * Clock In an employee on their behalf.
     */
    public function employeeClockIn($id)
    {
        $manager = $this->getActiveManager();
        $employee = User::where('id', $id)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        $todayStr = Carbon::today()->toDateString();
        $existing = AttendanceLog::where('user_id', $employee->id)
            ->whereDate('date', $todayStr)
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Employee has already clocked in today.');
        }

        // Determine if late (e.g. after 09:30:00)
        $now = Carbon::now();
        $status = 'present';
        if ($now->hour > 9 || ($now->hour == 9 && $now->minute > 30)) {
            $status = 'late';
        }

        AttendanceLog::create([
            'user_id' => $employee->id,
            'date' => $todayStr,
            'check_in' => $now->toTimeString(),
            'status' => $status,
        ]);

        return redirect()->back()->with('success', 'Employee clocked in successfully by manager. Status: ' . ucfirst($status));
    }

    /**
     * Clock Out an employee on their behalf.
     */
    public function employeeClockOut($id)
    {
        $manager = $this->getActiveManager();
        $employee = User::where('id', $id)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        $todayStr = Carbon::today()->toDateString();
        $attendance = AttendanceLog::where('user_id', $employee->id)
            ->whereDate('date', $todayStr)
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', 'Employee must be clocked in before you can clock them out.');
        }

        if ($attendance->check_out) {
            return redirect()->back()->with('error', 'Employee has already clocked out today.');
        }

        // Stop any running task timers first
        $activeTimer = TimeEntry::where('user_id', $employee->id)
            ->whereNull('stopped_at')
            ->first();

        if ($activeTimer) {
            $stoppedAt = Carbon::now();
            $durationSeconds = (int) abs($stoppedAt->diffInSeconds(Carbon::parse($activeTimer->started_at)));
            $activeTimer->update([
                'stopped_at' => $stoppedAt,
                'duration_seconds' => $durationSeconds,
            ]);
        }

        $now = Carbon::now();
        $isEarlyExit = $now->hour < 17;

        $attendance->update([
            'check_out' => $now->toTimeString(),
            'is_early_exit' => $isEarlyExit,
        ]);

        $message = 'Employee clocked out successfully by manager.';
        if ($isEarlyExit) {
            $message .= ' Note: Early checkout recorded.';
        }
        if ($activeTimer) {
            $message .= ' Running task timer was also stopped.';
        }

        return redirect()->back()->with('success', $message);
    }
}
