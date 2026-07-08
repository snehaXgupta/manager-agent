<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProjectController extends Controller
{
    protected function getActiveManager()
    {
        return auth()->user();
    }

    /**
     * List projects managed by the current manager.
     */
    public function index(Request $request)
    {
        $manager = $this->getActiveManager();
        
        $query = Project::where('manager_id', $manager->id)
            ->withCount('members');

        // Filter archived
        $viewArchived = $request->boolean('view_archived', false);
        $query->where('is_archived', $viewArchived);

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 1. Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 2. Sorting
        $sortBy = $request->query('sort_by', 'name_asc');
        if ($sortBy === 'name_desc') {
            $query->orderBy('name', 'desc');
        } else {
            $query->orderBy('name', 'asc');
        }

        $projects = $query->paginate(12)->withQueryString();

        // 3. Extra sorting in memory
        if ($sortBy === 'tasks_desc' || $sortBy === 'completion_desc') {
            foreach ($projects as $project) {
                $allTasksQuery = Task::where('project_id', $project->id);
                $totalTasks = $allTasksQuery->count();
                $completedTasksCount = (clone $allTasksQuery)->where('status', 'completed')->count();
                $project->total_tasks_count = $totalTasks;
                $project->task_completion_rate = $totalTasks > 0 ? ($completedTasksCount / $totalTasks) * 100 : 100.0;
            }

            if ($sortBy === 'tasks_desc') {
                $sorted = $projects->getCollection()->sortByDesc('total_tasks_count')->values();
            } else {
                $sorted = $projects->getCollection()->sortByDesc('task_completion_rate')->values();
            }
            $projects->setCollection($sorted);
        }

        // Fetch direct report employees for the creation form (limit to 100 to avoid DOM bloating)
        $employees = User::where('manager_id', $manager->id)
            ->where('role', 'employee')
            ->take(100)
            ->get();

        return view('dashboard.projects.index', compact('projects', 'employees'));
    }

    /**
     * Create a new project, correspond to GitLab repository and sync members.
     */
    public function store(Request $request, \App\Services\GitlabProjectService $projectService, \App\Services\GitlabMemberService $memberService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id',
            'category' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'repo_mode' => 'nullable|string|in:new,existing',
            'existing_repo_url' => 'required_if:repo_mode,existing|nullable|url',
            'existing_gitlab_project_id' => 'required_if:repo_mode,existing|nullable|integer',
        ]);

        $manager = $this->getActiveManager();

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'manager_id' => $manager->id,
            'category' => $request->category ?: 'Development',
            'deadline' => $request->deadline ? Carbon::parse($request->deadline) : null,
            'status' => 'active',
        ]);

        if ($request->has('members')) {
            $project->members()->sync($request->members);
        }

        // GitLab repository creation or association
        if ($request->repo_mode === 'existing') {
            $repo = $projectService->associateExisting(
                $project,
                $request->existing_repo_url,
                $request->existing_gitlab_project_id
            );
        } else {
            $repo = $projectService->createRepository($project, 'private');
        }

        if ($repo) {
            // Automatically add project members who have mapped GitLab accounts
            $project->load('members');
            foreach ($project->members as $member) {
                if ($member->gitlab_user_id) {
                    $memberService->addMemberToRepository($project, $member);
                }
            }
        }

        return redirect()->route('dashboard.projects.index')->with('success', 'Project created and GitLab repository associated successfully!');
    }

    /**
     * Show project workspace dashboard.
     */
    public function show($id)
    {
        $manager = $this->getActiveManager();
        $project = Project::where('id', $id)
            ->where('manager_id', $manager->id)
            ->with(['members', 'repository'])
            ->firstOrFail();

        // Paginate tasks to handle large volumes (e.g. 50,000 total tasks)
        $tasks = Task::where('project_id', $project->id)
            ->with('assignee')
            ->orderByRaw("CASE WHEN status = 'completed' THEN 2 ELSE 1 END")
            ->orderBy('deadline', 'asc')
            ->paginate(15);

        // Fetch project metrics
        $allTasksQuery = Task::where('project_id', $project->id);
        $totalTasks = $allTasksQuery->count();
        $completedTasksCount = (clone $allTasksQuery)->where('status', 'completed')->count();
        
        $taskCompletionRate = $totalTasks > 0 ? ($completedTasksCount / $totalTasks) * 100 : 100.0;
        
        $completedOnTimeCount = (clone $allTasksQuery)->where('status', 'completed')
            ->where(function ($query) {
                $query->whereNull('deadline')
                      ->orWhereColumn('updated_at', '<=', 'deadline');
            })
            ->count();
            
        $deadlineAdherenceRate = $completedTasksCount > 0 
            ? ($completedOnTimeCount / $completedTasksCount) * 100 
            : 100.0;

        // Calculate Project Health Score Components
        // 1. Progress Component (40 pts)
        $progressPoints = ($taskCompletionRate / 100) * 40;

        // 2. Deadline Component (40 pts)
        if ($project->deadline) {
            $deadline = Carbon::parse($project->deadline);
            if (Carbon::now()->gt($deadline) && $project->status !== 'completed') {
                $deadlinePoints = 0;
            } elseif ($project->status === 'completed') {
                $deadlinePoints = 40;
            } else {
                $overduePendingTasks = Task::where('project_id', $project->id)
                    ->where('status', '!=', 'completed')
                    ->where('deadline', '<', Carbon::now())
                    ->count();
                $deadlinePoints = 40 - ($overduePendingTasks > 0 ? min(40, ($overduePendingTasks / max(1, $totalTasks)) * 40) : 0);
            }
        } else {
            $deadlinePoints = ($deadlineAdherenceRate / 100) * 40;
        }

        // 3. Risk Component (20 pts)
        $overloadedMembersCount = 0;
        $inactiveMembersCount = 0;
        foreach ($project->members as $member) {
            $memberTasksCount = Task::where('project_id', $project->id)
                ->where('assigned_to', $member->id)
                ->where('status', '!=', 'completed')
                ->count();
            if ($memberTasksCount > 5) {
                $overloadedMembersCount++;
            }
            
            $memberTotalTasksCount = Task::where('project_id', $project->id)
                ->where('assigned_to', $member->id)
                ->count();
            if ($memberTotalTasksCount === 0) {
                $inactiveMembersCount++;
            }
        }
        
        $overloadPenalty = $overloadedMembersCount * 5;
        $inactivityPenalty = $inactiveMembersCount * 3;

        // GitLab Inactivity Penalty
        $gitInactivityPenalty = 0;
        $repository = $project->repository;
        if ($repository) {
            $latestCommit = \App\Models\Commit::where('project_id', $project->id)
                ->orderBy('committed_at', 'desc')
                ->first();
            if (!$latestCommit || Carbon::parse($latestCommit->committed_at)->lt(Carbon::now()->subDays(5))) {
                $gitInactivityPenalty = 10;
            }
        }

        $riskPenalties = $overloadPenalty + $inactivityPenalty + $gitInactivityPenalty;
        $riskPoints = max(0, 20 - $riskPenalties);

        $healthScore = round($progressPoints + $deadlinePoints + $riskPoints);

        // Include warnings/alerts for risk factors
        $healthWarnings = [];
        if ($overloadedMembersCount > 0) {
            $healthWarnings[] = "{$overloadedMembersCount} team member(s) are overloaded (> 5 pending tasks).";
        }
        if ($inactiveMembersCount > 0) {
            $healthWarnings[] = "{$inactiveMembersCount} team member(s) have no tasks assigned.";
        }
        if ($gitInactivityPenalty > 0 && $repository) {
            $healthWarnings[] = "No recent GitLab commits detected in the last 5 days.";
        }
        if ($project->deadline && Carbon::now()->gt(Carbon::parse($project->deadline)) && $project->status !== 'completed') {
            $healthWarnings[] = "Project is past its target deadline and is incomplete.";
        }

        $projectMetrics = [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasksCount,
            'task_completion_rate' => round($taskCompletionRate, 2),
            'deadline_adherence_rate' => round($deadlineAdherenceRate, 2),
            'health_score' => $healthScore,
            'health_warnings' => $healthWarnings,
        ];

        // Fetch members list for task assignments dropdown (limit to 100)
        $employees = $project->members()->take(100)->get();

        return view('dashboard.projects.show', compact('project', 'tasks', 'projectMetrics', 'employees'));
    }

    /**
     * Dissolve/Delete a project.
     */
    public function destroy($id)
    {
        $manager = $this->getActiveManager();
        $project = Project::where('id', $id)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        $project->delete();

        return redirect()->route('dashboard.projects.index')->with('success', 'Project dissolved successfully!');
    }

    /**
     * Create a task directly within the project workspace.
     */
    public function storeTask(Request $request, $projectId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $manager = $this->getActiveManager();
        $project = Project::where('id', $projectId)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        // Verify assignee is a member of this project if set
        if ($request->filled('assigned_to')) {
            $isMember = $project->members()->where('users.id', $request->assigned_to)->exists();
            if (!$isMember) {
                return redirect()->back()->with('error', 'Assigned employee must be a member of this project.');
            }
        }

        Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'deadline' => $request->deadline ? Carbon::parse($request->deadline) : null,
            'assigned_to' => $request->assigned_to ?: null,
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        return redirect()->route('dashboard.projects.show', $projectId)->with('success', 'Task added to project successfully!');
    }

    /**
     * Sync repository members manually.
     */
    public function syncMembers($id, \App\Services\GitlabMemberService $memberService)
    {
        $manager = $this->getActiveManager();
        $project = Project::where('id', $id)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        $memberService->syncMembers($project);

        return redirect()->back()->with('success', 'Project members synchronized with GitLab successfully.');
    }

    /**
     * Sync repository commits manually.
     */
    public function syncCommits($id, \App\Services\GitlabCommitService $commitService)
    {
        $manager = $this->getActiveManager();
        $project = Project::where('id', $id)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        $repo = $project->repository;
        if (!$repo) {
            return redirect()->back()->with('error', 'No GitLab repository associated with this project.');
        }

        $count = $commitService->syncCommitsForRepository($repo);

        return redirect()->back()->with('success', "Synchronized {$count} new commits from GitLab.");
    }

    /**
     * Sync repository merge requests manually.
     */
    public function syncMergeRequests($id, \App\Services\GitlabMergeRequestService $mrService)
    {
        $manager = $this->getActiveManager();
        $project = Project::where('id', $id)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        $repo = $project->repository;
        if (!$repo) {
            return redirect()->back()->with('error', 'No GitLab repository associated with this project.');
        }

        $count = $mrService->syncMergeRequestsForRepository($repo);

        return redirect()->back()->with('success', "Synchronized {$count} merge requests from GitLab.");
    }

    /**
     * Sync repository reviews manually.
     */
    public function syncReviews($id, \App\Services\GitlabMergeRequestService $mrService)
    {
        $manager = $this->getActiveManager();
        $project = Project::where('id', $id)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        $repo = $project->repository;
        if (!$repo) {
            return redirect()->back()->with('error', 'No GitLab repository associated with this project.');
        }

        $count = $mrService->syncReviewsForMergeRequests($repo);

        return redirect()->back()->with('success', "Synchronized {$count} review comments/approvals from GitLab.");
    }

    /**
     * Update project settings.
     */
    public function update(Request $request, $id, \App\Services\GitlabMemberService $memberService)
    {
        $manager = $this->getActiveManager();
        $project = Project::where('id', $id)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:255',
            'status' => 'required|string|in:active,on_hold,completed',
            'deadline' => 'nullable|date',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id',
        ]);

        $project->update([
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'status' => $request->status,
            'deadline' => $request->deadline ? Carbon::parse($request->deadline) : null,
        ]);

        if ($request->has('members')) {
            $project->members()->sync($request->members);
            
            // Re-sync any new GitLab mappings
            $project->load('members');
            if ($project->repository) {
                foreach ($project->members as $member) {
                    if ($member->gitlab_user_id) {
                        $memberService->addMemberToRepository($project, $member);
                    }
                }
            }
        } else {
            $project->members()->sync([]);
        }

        return redirect()->back()->with('success', 'Project updated successfully!');
    }

    /**
     * Toggle project archive state.
     */
    public function toggleArchive($id)
    {
        $manager = $this->getActiveManager();
        $project = Project::where('id', $id)
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        $project->update([
            'is_archived' => !$project->is_archived
        ]);

        $message = $project->is_archived ? 'Project archived successfully.' : 'Project unarchived successfully.';
        return redirect()->route('dashboard.projects.index')->with('success', $message);
    }
}
