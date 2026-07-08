<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use App\Services\PerformanceAnalyticsService;
use App\Services\OllamaAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TeamController extends Controller
{
    protected $analyticsService;
    protected $aiService;

    public function __construct(PerformanceAnalyticsService $analyticsService, OllamaAiService $aiService)
    {
        $this->analyticsService = $analyticsService;
        $this->aiService = $aiService;
    }

    protected function getActiveManager()
    {
        return auth()->user();
    }

    public function index(Request $request)
    {
        $manager = $this->getActiveManager();
        
        $query = Team::where('manager_id', $manager->id)
            ->with(['members.manager', 'manager'])
            ->withCount('members');

        // 1. Search filter
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // 2. Members count filter
        if ($request->filled('members')) {
            $membersFilter = $request->members;
            if ($membersFilter === 'empty') {
                $query->has('members', '=', 0);
            } elseif ($membersFilter === 'small') {
                $query->has('members', '>=', 1)->has('members', '<=', 5);
            } elseif ($membersFilter === 'large') {
                $query->has('members', '>', 5);
            }
        }

        // 3. Sorting
        $sortBy = $request->query('sort_by', 'name_asc');
        if ($sortBy === 'name_desc') {
            $query->orderBy('name', 'desc');
        } elseif ($sortBy === 'members_desc') {
            $query->orderBy('members_count', 'desc');
        } elseif ($sortBy === 'members_asc') {
            $query->orderBy('members_count', 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        $teams = $query->paginate(20)->withQueryString();

        // Get all employees of this manager to display in team creation form
        $employees = User::where('manager_id', $manager->id)
            ->where('role', 'employee')
            ->take(100)
            ->get();

        return view('dashboard.teams.index', compact('teams', 'employees'));
    }

    /**
     * Create a new team and sync members.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id',
        ]);

        $manager = $this->getActiveManager();

        $team = Team::create([
            'name' => $request->name,
            'manager_id' => $manager->id,
        ]);

        if ($request->has('members')) {
            $team->members()->sync($request->members);
        }

        return redirect()->route('dashboard.teams.index')->with('success', 'Team formed successfully!');
    }

    /**
     * Show a team dashboard containing members, team tasks, scheduled meetings, and AI team analysis.
     */
    public function show($id)
    {
        $manager = $this->getActiveManager();
        $team = Team::where('id', $id)
            ->where('manager_id', $manager->id)
            ->with(['members', 'meetings', 'tasks'])
            ->firstOrFail();

        // Calculate performance metrics specifically for the members of this team
        $memberIds = $team->members->pluck('id');
        
        $metrics = $this->analyticsService->calculateTeamMetrics(
            $manager->id,
            Carbon::now()->subDays(6)->startOfDay(),
            Carbon::now()->endOfDay()
        );

        // Adjust metrics to reflect only team members if the team has members
        if ($memberIds->isNotEmpty()) {
            // Re-calculate using the specific team member IDs
            // We can invoke the protected calculateMetricsForUserIds using reflection or simply we can expose it
            // Oh, wait! The calculateTeamMetrics method calculates for all manager's employees.
            // Let's look at PerformanceAnalyticsService. It has calculateUserMetrics and calculateTeamMetrics.
            // Wait! PerformanceAnalyticsService doesn't have a public calculateForUserIds method.
            // Let's double check App\Services\PerformanceAnalyticsService.
            // Ah! calculateMetricsForUserIds is protected.
            // Let's check if we can add a public method or make it public.
            // Wait, we can just edit PerformanceAnalyticsService.php to make it public! That's super clean and reusable.
            // Let's do that! That's a tiny tweak that makes our code extremely robust and clean.
        }

        // Wait! Let's get the specific team metrics if the pivot table has members
        if ($memberIds->isNotEmpty()) {
            // We will call a public method calculateMetricsForUserIds on PerformanceAnalyticsService
            // Let's make it public!
            $teamMetrics = $this->analyticsService->calculateMetricsForUserIds($memberIds, Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay());
        } else {
            $teamMetrics = [
                'team_size' => 0,
                'task_completion_rate' => 0.0,
                'deadline_adherence_rate' => 0.0,
                'productivity_score' => 0.0,
                'consistency_score' => 0.0,
                'manager_score' => 0.0,
                'metrics_breakdown' => [
                    'total_assigned_tasks' => 0,
                    'completed_tasks' => 0,
                    'completed_on_time_tasks' => 0,
                    'total_hours_logged' => 0.0,
                    'expected_hours' => 0.0,
                ]
            ];
        }

        // Generate AI Team Analysis Report
        $insights = \Illuminate\Support\Facades\Cache::remember("team_insights_{$team->id}", 600, function () use ($team, $teamMetrics) {
            return $this->aiService->generateTeamInsights($team, $teamMetrics);
        });

        // Fetch tasks specific to this team OR tasks assigned to the members of this team
        // Let's merge both: tasks with team_id = $team->id, plus any tasks assigned to members that don't have a team_id
        $tasks = Task::where('team_id', $team->id)
            ->orWhere(function ($query) use ($memberIds) {
                $query->whereIn('assigned_to', $memberIds)->whereNull('team_id');
            })
            ->with('assignee')
            ->orderByRaw("CASE WHEN status = 'completed' THEN 2 ELSE 1 END")
            ->orderBy('deadline', 'asc')
            ->get();

        $meetings = Meeting::where('team_id', $team->id)
            ->with(['transcript', 'actionItems.assignee', 'decisions', 'creator'])
            ->orderBy('meeting_date', 'desc')
            ->orderBy('meeting_time', 'desc')
            ->get();

        return view('dashboard.teams.show', compact('team', 'teamMetrics', 'insights', 'tasks', 'meetings'));
    }

    /**
     * Delete/Dissolve a team.
     */
    public function destroy($id)
    {
        $manager = $this->getActiveManager();
        $team = Team::where('id', $id)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        $team->delete();

        return redirect()->route('dashboard.teams.index')->with('success', 'Team dissolved successfully!');
    }

    /**
     * Schedule a meeting for the team.
     */
    public function storeMeeting(Request $request, $teamId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date',
        ]);

        $manager = $this->getActiveManager();
        $team = Team::where('id', $teamId)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        Meeting::create([
            'title' => $request->title,
            'description' => $request->description,
            'scheduled_at' => Carbon::parse($request->scheduled_at),
            'team_id' => $team->id,
            'manager_id' => $manager->id,
        ]);

        return redirect()->route('dashboard.teams.show', $teamId)->with('success', 'Meeting scheduled successfully!');
    }

    /**
     * Post/Update meeting notes.
     */
    public function updateMeetingNotes(Request $request, $teamId, $meetingId)
    {
        $request->validate([
            'meeting_notes' => 'required|string',
        ]);

        $manager = $this->getActiveManager();
        $meeting = Meeting::where('id', $meetingId)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        $meeting->update([
            'meeting_notes' => $request->meeting_notes,
        ]);

        return redirect()->route('dashboard.teams.show', $teamId)->with('success', 'Meeting notes saved successfully!');
    }

    /**
     * Assign a task to the team (and optionally assign to a specific member).
     */
    public function storeTask(Request $request, $teamId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $manager = $this->getActiveManager();
        $team = Team::where('id', $teamId)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'deadline' => $request->deadline ? Carbon::parse($request->deadline) : null,
            'assigned_to' => $request->assigned_to ?: null,
            'team_id' => $team->id,
            'status' => 'pending',
        ]);

        return redirect()->route('dashboard.teams.show', $teamId)->with('success', 'Task assigned to team successfully!');
    }
}
