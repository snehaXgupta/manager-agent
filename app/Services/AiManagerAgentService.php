<?php

namespace App\Services;

use App\Models\User;
use App\Models\Team;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\AttendanceLog;
use App\Models\RiskAlert;
use App\Models\DeveloperActivity;
use App\Models\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AiManagerAgentService
{
    protected $baseUrl;
    protected $model;
    protected $timeout;
    protected $apiKey;
    protected $isNvidia = false;

    public function __construct()
    {
        $this->apiKey = config('services.nvidia.api_key');
        if (!empty($this->apiKey)) {
            $this->isNvidia = true;
            $this->baseUrl = config('services.nvidia.base_url', 'https://integrate.api.nvidia.com/v1');
            $this->model = config('services.nvidia.model', 'meta/llama-3.1-8b-instruct');
            $this->timeout = 30;
        } else {
            $this->baseUrl = config('services.ollama.base_url', 'http://localhost:11434');
            $this->model = config('services.ollama.model', 'llama3.1:8b');
            $this->timeout = 10;
        }
    }

    protected function isGreeting(string $question): bool
    {
        $q = trim(strtolower($question));
        $q = preg_replace('/[?.!,;:]+$/', '', $q);
        $q = trim($q);
        
        $greetings = ['hi', 'hello', 'hey', 'greetings', 'sup', 'yo', 'good morning', 'good afternoon', 'good evening', 'hola', 'hi there', 'hello there'];
        return in_array($q, $greetings);
    }

    protected function getGreetingResponse(): array
    {
        return [
            'direct_answer' => "Hello! How can I assist you today? I can help you analyze team performance, check attendance trends, view active burnout/deadline risks, or review GitLab commit metrics.",
            'supporting_metrics' => [],
            'ai_analysis' => "Telemetry engine is on standby. Awaiting search query parameters.",
            'recommendations' => [
                "Ask 'Who is the top performer this month?'",
                "Ask 'Show active burnout and deadline risks alerts'",
                "Ask 'Which team is overloaded?'"
            ],
            'data_sources_used' => [],
            'visual_type' => 'null',
            'visual_data' => null,
            'engine' => 'Greeting Router'
        ];
    }

    /**
     * Parse question, aggregate security-scoped context, query Ollama/NVIDIA, and return structured response.
     */
    public function ask(string $question, array $chatHistory = [], ?string $startDate = null, ?string $endDate = null): array
    {
        if ($this->isGreeting($question)) {
            return $this->getGreetingResponse();
        }

        $user = auth()->user();
        $role = session('active_role', $user->role);

        // Default to weekly report range if none provided
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(7)->toDateString();
        }
        if (!$endDate) {
            $endDate = Carbon::now()->toDateString();
        }

        // 1. Gather live structured data scoped to user permissions
        $contextData = $this->gatherContextData($question, $user, $role, $startDate, $endDate);

        // 2. Fallback check: If AI Service is offline, use high-fidelity rule-based response builder
        if ($this->isOllamaOffline()) {
            return $this->buildFallbackResponse($question, $contextData, "AI Service Offline (Local Fallback Mode)");
        }

        // 3. Construct prompt with compiled database context and memory
        $prompt = $this->compilePrompt($question, $contextData, $chatHistory);

        // 4. Send request to Ollama/NVIDIA
        try {
            $url = $this->isNvidia 
                ? rtrim($this->baseUrl, '/') . '/chat/completions'
                : rtrim($this->baseUrl, '/') . '/api/chat';

            $headers = [
                'Content-Type' => 'application/json',
            ];
            if ($this->isNvidia) {
                $headers['Authorization'] = 'Bearer ' . $this->apiKey;
            }

            $payload = [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
            ];

            if ($this->isNvidia) {
                $payload['temperature'] = 0.2;
                $payload['top_p'] = 0.7;
                $payload['max_tokens'] = 2048;
                $payload['response_format'] = ['type' => 'json_object'];
            } else {
                $payload['stream'] = false;
                $payload['format'] = 'json';
            }

            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->post($url, $payload);

            if ($response->failed()) {
                throw new Exception('AI connection failed: ' . $response->body());
            }

            $body = $response->json();
            
            $content = $this->isNvidia
                ? ($body['choices'][0]['message']['content'] ?? '')
                : ($body['message']['content'] ?? '');
            
            $structured = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($structured['direct_answer'])) {
                throw new Exception('Invalid JSON structure returned by AI model.');
            }

            return $structured;

        } catch (Exception $e) {
            Log::warning("AI Chat Service Exception: " . $e->getMessage() . ". Falling back to rule-based engine.");
            return $this->buildFallbackResponse($question, $contextData, "AI Fallback Engine");
        }
    }

    /**
     * Retrieve structured database context scoped to current role.
     */
    public function gatherContextData(string $question, User $user, string $role, ?string $startDate = null, ?string $endDate = null): array
    {
        $data = [
            'scope_role' => $role,
            'scope_user' => $user->name,
            'timestamp' => Carbon::now()->toDateTimeString(),
            'employees' => [],
            'teams' => [],
            'projects' => [],
            'tasks_summary' => [],
            'attendance_summary' => [],
            'gitlab_summary' => [],
            'risks' => [],
            'specific_entity' => null
        ];

        // Access scope helpers
        $allowedUserIds = $this->getAllowedUserIds($user, $role);
        $allowedProjectIds = $this->getAllowedProjectIds($user, $role);
        $allowedTeamIds = $this->getAllowedTeamIds($user, $role);

        // Parse specific employee details if mentioned
        $mentionedUser = $this->detectMentionedUser($question, $allowedUserIds);
        if ($mentionedUser) {
            $data['specific_entity'] = [
                'type' => 'employee',
                'id' => $mentionedUser->id,
                'name' => $mentionedUser->name,
                'email' => $mentionedUser->email,
                'metrics' => $this->getEmployeeSpecificMetrics($mentionedUser, $startDate, $endDate)
            ];
        }

        // Fetch basic employees
        $employeesQuery = User::whereIn('id', $allowedUserIds)->where('role', 'employee');
        $data['employees_count'] = $employeesQuery->count();
        $data['employees'] = $employeesQuery->take(15)->get(['id', 'name', 'email'])->toArray();

        // Fetch teams
        $teamsQuery = Team::whereIn('id', $allowedTeamIds);
        $data['teams_count'] = $teamsQuery->count();
        $data['teams'] = $teamsQuery->withCount('members')->take(10)->get(['id', 'name'])->toArray();

        // Fetch projects
        $projectsQuery = Project::whereIn('id', $allowedProjectIds);
        $data['projects_count'] = $projectsQuery->count();
        $data['projects'] = $projectsQuery->take(10)->get(['id', 'name', 'description'])->toArray();

        // Tasks status summary
        $tasksQuery = Task::whereIn('assigned_to', $allowedUserIds);
        if ($startDate) {
            $tasksQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $tasksQuery->where('created_at', '<=', $endDate);
        }
        $data['tasks_summary'] = [
            'total' => (clone $tasksQuery)->count(),
            'completed' => (clone $tasksQuery)->where('status', 'completed')->count(),
            'in_progress' => (clone $tasksQuery)->where('status', 'in_progress')->count(),
            'pending' => (clone $tasksQuery)->where('status', 'pending')->count(),
            'overdue' => (clone $tasksQuery)->where('status', '!=', 'completed')->where('deadline', '<', Carbon::now())->count(),
        ];

        // Attendance stats
        $attQuery = AttendanceLog::whereIn('user_id', $allowedUserIds);
        if ($startDate) {
            $attQuery->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $attQuery->where('date', '<=', $endDate);
        }
        $isToday = !$startDate && !$endDate;
        if ($isToday) {
            $attQuery->where('date', Carbon::today()->toDateString());
        }

        $data['attendance_summary'] = [
            'present_today' => (clone $attQuery)->where('status', 'present')->count(),
            'late_today' => (clone $attQuery)->where('status', 'late')->count(),
            'absent_today' => (clone $attQuery)->where('status', 'absent')->count(),
            'is_range' => !$isToday,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        // GitLab contribution aggregates
        $gitCommitsQuery = DeveloperActivity::whereIn('user_id', $allowedUserIds)->where('event_type', 'commit');
        $gitReviewsQuery = DeveloperActivity::whereIn('user_id', $allowedUserIds)->where('event_type', 'review_submitted');
        if ($startDate) {
            $gitCommitsQuery->where('created_at', '>=', $startDate);
            $gitReviewsQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $gitCommitsQuery->where('created_at', '<=', $endDate);
            $gitReviewsQuery->where('created_at', '<=', $endDate);
        }
        $data['gitlab_summary'] = [
            'total_commits' => $gitCommitsQuery->count(),
            'total_reviews' => $gitReviewsQuery->count(),
        ];

        // Risks
        $data['risks'] = RiskAlert::whereIn('employee_id', $allowedUserIds)
            ->where('is_resolved', false)
            ->with('employee:id,name')
            ->take(5)
            ->get()
            ->toArray();

        // Top/Bottom rankings
        $data['leaderboards'] = [
            'top_performers' => User::whereIn('id', $allowedUserIds)
                ->where('role', 'employee')
                ->withCount(['tasks' => fn($q) => $q->where('status', 'completed')])
                ->orderBy('tasks_count', 'desc')
                ->take(5)
                ->get(['id', 'name', 'email'])
                ->toArray(),
            'bottom_performers' => User::whereIn('id', $allowedUserIds)
                ->where('role', 'employee')
                ->withCount(['tasks' => fn($q) => $q->where('status', 'completed')])
                ->orderBy('tasks_count', 'asc')
                ->take(5)
                ->get(['id', 'name', 'email'])
                ->toArray()
        ];

        return $data;
    }

    /**
     * Context awareness Prompt constructor
     */
    protected function compilePrompt(string $question, array $contextData, array $chatHistory): string
    {
        $contextJson = json_encode($contextData, JSON_PRETTY_PRINT);
        $historyText = "";
        foreach (array_slice($chatHistory, -4) as $msg) {
            $roleName = $msg['role'] === 'user' ? 'Manager' : 'AI Assistant';
            $historyText .= "{$roleName}: {$msg['content']}\n";
        }

        return <<<PROMPT
You are a friendly, concise AI Assistant for a workforce management platform (like a helpful customer support bot).
Your objective is to answer the user's question directly, accurately, and concisely using the provided context. Avoid robotic or overly academic language. Only answer what is needed to solve the user's query.
Do NOT fabricate data. If the answer cannot be found in the context, politely state that you don't have that information.

--- WORKSPACE CONTEXT DATA ---
{$contextJson}

--- RECENT CHAT HISTORY ---
{$historyText}

--- CURRENT QUESTION ---
Manager: {$question}

Instructions for your response:
1. Provide a direct, professional, corporate answer to the manager's question.
2. Under "supporting_metrics", list 3-5 core metrics derived from the context.
3. Under "ai_analysis", explain the underlying factors or performance patterns.
4. Under "recommendations", provide 2-3 actionable advice items.
5. Under "data_sources_used", specify which modules were read (e.g. "employees", "tasks", "attendance", "gitlab_metrics", "risk_center").
6. You may output a structured visualization format in "visual_type" ("leaderboard", "table", "chart", "kpi", "null") and "visual_data" if applicable.
   - For chart, specify "chart_type" ("bar", "line", "pie"), "chart_labels" (array), and "chart_values" (array).
   - For table or leaderboard, specify "headers" and "rows" arrays.
7. If the user asks for "measures to resolve", "how to resolve", "remedies", or "fix" any issues, you MUST suggest actionable resolution measures in a structured table layout (visual_type='table') with headers ["Workspace Exception", "Actionable Resolution Measure", "Priority"].

Return your entire response in strict JSON format matching the schema below:
{
  "direct_answer": "...",
  "supporting_metrics": ["...", "..."],
  "ai_analysis": "...",
  "recommendations": ["...", "..."],
  "data_sources_used": ["...", "..."],
  "visual_type": "leaderboard | table | chart | kpi | null",
  "visual_data": {
    "headers": ["...", "..."],
    "rows": [["...", "..."]],
    "chart_type": "bar | line | pie",
    "chart_labels": ["...", "..."],
    "chart_values": [10, 20]
  }
}
PROMPT;
    }

    /**
     * Security scoping: users.
     */
    protected function getAllowedUserIds(User $user, string $role): array
    {
        if ($role === 'admin') {
            return User::pluck('id')->toArray();
        } elseif ($role === 'manager') {
            return User::where('manager_id', $user->id)->orWhere('id', $user->id)->pluck('id')->toArray();
        } else {
            return [$user->id];
        }
    }

    /**
     * Security scoping: projects.
     */
    protected function getAllowedProjectIds(User $user, string $role): array
    {
        if ($role === 'admin') {
            return Project::pluck('id')->toArray();
        } elseif ($role === 'manager') {
            return Project::where('manager_id', $user->id)->pluck('id')->toArray();
        } else {
            return $user->projects()->pluck('projects.id')->toArray();
        }
    }

    /**
     * Security scoping: teams.
     */
    protected function getAllowedTeamIds(User $user, string $role): array
    {
        if ($role === 'admin') {
            return Team::pluck('id')->toArray();
        } elseif ($role === 'manager') {
            return Team::where('manager_id', $user->id)->pluck('id')->toArray();
        } else {
            return $user->teams()->pluck('teams.id')->toArray();
        }
    }

    /**
     * Parse query keywords to identify any mentioned users.
     */
    protected function detectMentionedUser(string $question, array $allowedUserIds): ?User
    {
        $users = User::whereIn('id', $allowedUserIds)->get();
        foreach ($users as $u) {
            // Check if name is in the question
            if (stripos($question, $u->name) !== false) {
                return $u;
            }
        }
        return null;
    }

    /**
     * Fetch detailed individual parameters.
     */
    protected function getEmployeeSpecificMetrics(User $employee, ?string $startDate = null, ?string $endDate = null): array
    {
        $tasksQuery = $employee->tasks();
        $commitsQuery = DeveloperActivity::where('user_id', $employee->id)->where('event_type', 'commit');
        $reviewsQuery = DeveloperActivity::where('user_id', $employee->id)->where('event_type', 'review_submitted');
        
        if ($startDate) {
            $tasksQuery->where('created_at', '>=', $startDate);
            $commitsQuery->where('created_at', '>=', $startDate);
            $reviewsQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $tasksQuery->where('created_at', '<=', $endDate);
            $commitsQuery->where('created_at', '<=', $endDate);
            $reviewsQuery->where('created_at', '<=', $endDate);
        }

        return [
            'total_tasks' => (clone $tasksQuery)->count(),
            'completed_tasks' => (clone $tasksQuery)->where('status', 'completed')->count(),
            'pending_tasks' => (clone $tasksQuery)->where('status', 'pending')->count(),
            'in_progress_tasks' => (clone $tasksQuery)->where('status', 'in_progress')->count(),
            'overdue_tasks' => (clone $tasksQuery)->where('status', '!=', 'completed')->where('deadline', '<', Carbon::now())->count(),
            'attendance_rate' => $this->calculateAttendanceRate($employee, $startDate, $endDate),
            'gitlab_commits' => $commitsQuery->count(),
            'gitlab_reviews' => $reviewsQuery->count(),
            'active_risks' => RiskAlert::where('employee_id', $employee->id)->where('is_resolved', false)->pluck('risk_type')->toArray()
        ];
    }

    protected function calculateAttendanceRate(User $employee, ?string $startDate = null, ?string $endDate = null): float
    {
        $totalDaysQuery = AttendanceLog::where('user_id', $employee->id);
        $presentQuery = AttendanceLog::where('user_id', $employee->id)->whereIn('status', ['present', 'late']);
        if ($startDate) {
            $totalDaysQuery->where('date', '>=', $startDate);
            $presentQuery->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $totalDaysQuery->where('date', '<=', $endDate);
            $presentQuery->where('date', '<=', $endDate);
        }
        $totalDays = $totalDaysQuery->count();
        if ($totalDays === 0) return 100.0;
        $present = $presentQuery->count();
        return round(($present / $totalDays) * 100, 1);
    }

    protected function isOllamaOffline(): bool
    {
        return \Illuminate\Support\Facades\Cache::get('ollama_offline', false);
    }

    /**
     * High fidelity fallback response compiler
     */
    protected function buildFallbackResponse(string $question, array $contextData, string $engineName): array
    {
        $q = strtolower($question);
        
        $direct = "I have analyzed the current workspace parameters.";
        $metrics = ["Total Team size: " . ($contextData['employees_count'] ?? 0) . " members"];
        $analysis = "Based on the structured workforce database query result.";
        $recs = ["Ensure deadlines are tracked and workloads balanced."];
        $sources = ["employees", "tasks"];
        
        $vType = "null";
        $vData = null;

        // Route fallback logic based on intent
        if (stripos($q, 'performer') !== false || stripos($q, 'promotion') !== false || stripos($q, 'promoted') !== false) {
            $sources = ["employees", "tasks", "gitlab_metrics"];
            
            // Check if user is asking for worst/bottom performer
            $isWorstQuery = (
                stripos($q, 'worst') !== false || 
                stripos($q, 'bottom') !== false || 
                stripos($q, 'lowest') !== false || 
                stripos($q, 'least') !== false || 
                stripos($q, 'poor') !== false
            );

            if ($isWorstQuery) {
                $performers = $contextData['leaderboards']['bottom_performers'] ?? [];
                if (!empty($performers)) {
                    $worst = $performers[0]['name'];
                    $direct = "The lowest performing employee in the database based on completed tasks is currently {$worst}.";
                    $metrics = [
                        "Lowest completed tasks: " . ($performers[0]['tasks_count'] ?? 0),
                        "Total team tasks completed: " . ($contextData['tasks_summary']['completed'] ?? 0),
                    ];
                    $analysis = "Performance levels are based on completed deliverables. Bottom performers have completed the fewest tasks.";
                    $recs = [
                        "Consider scheduling a sync session with {$worst} to identify blockers.",
                        "Verify if tasks are correctly assigned or if additional support is needed."
                    ];

                    $vType = "leaderboard";
                    $rows = [];
                    foreach ($performers as $p) {
                        $rows[] = [$p['name'], ($p['tasks_count'] ?? 0) . " Tasks", "Active"];
                    }
                    $vData = [
                        "headers" => ["Employee", "Completed Tasks", "Status"],
                        "rows" => $rows
                    ];
                } else {
                    $direct = "Insufficient data available. There are no bottom performers tracked in the database.";
                }
            } else {
                $performers = $contextData['leaderboards']['top_performers'] ?? [];
                if (!empty($performers)) {
                    $top = $performers[0]['name'];
                    $direct = "The top performing employee in the database is currently {$top}.";
                    $metrics = [
                        "Top completed tasks: " . ($performers[0]['tasks_count'] ?? 5),
                        "Total team tasks completed: " . ($contextData['tasks_summary']['completed'] ?? 0),
                    ];
                    $analysis = "Performance levels are based on completed deliverables and activity indices.";
                    $recs = [
                        "Consider recognizing the high output of {$top}.",
                        "Ensure workload distribution is balanced to avoid burnout."
                    ];

                    $vType = "leaderboard";
                    $rows = [];
                    foreach ($performers as $p) {
                        $rows[] = [$p['name'], ($p['tasks_count'] ?? 0) . " Tasks", "Active"];
                    }
                    $vData = [
                        "headers" => ["Employee", "Completed Tasks", "Status"],
                        "rows" => $rows
                    ];
                } else {
                    $direct = "Insufficient data available. There are no completed tasks tracked in the database to build a performer ranking.";
                }
            }
        } elseif (stripos($q, 'resolve') !== false || stripos($q, 'measure') !== false || stripos($q, 'how to fix') !== false || stripos($q, 'remedy') !== false || stripos($q, 'improve') !== false) {
            $sources = ["risks", "tasks", "employees"];
            $riskCount = count($contextData['risks'] ?? []);
            $overdueCount = $contextData['tasks_summary']['overdue'] ?? 0;
            
            $direct = "Actionable measures to resolve workspace issues:\n" .
                      "1. Balance Workloads: Reallocate pending tasks from overloaded employees to underutilized members.\n" .
                      "2. Address Overdue Tasks: Extend task deadline parameters or hold blocker-clearing sync check-ins.\n" .
                      "3. Mitigate Burnout: Cap consecutive weekly hours at 40h and ensure rest intervals.\n" .
                      "4. Track Progress: Configure automatic notification triggers for upcoming deadline milestones.";
            
            $metrics = [
                "Active Risks: {$riskCount}",
                "Overdue Deliverables: {$overdueCount}",
                "Pending Tasks: " . ($contextData['tasks_summary']['pending'] ?? 0)
            ];
            
            $analysis = "Measures prioritize workload balancing, stress mitigation, and scheduling flexibility.";
            $recs = [
                "Review tasks tables to inspect individual load rates.",
                "Verify task assignees before scheduling check-ins."
            ];

            $vType = "table";
            $vData = [
                "headers" => ["Workspace Exception", "Actionable Resolution Measure", "Priority"],
                "rows" => [
                    ["Burnout / Overload", "Reallocate tasks; cap weekly hours at 40h", "High"],
                    ["Overdue Tasks", "Extend deadline or hold stand-up blocker sync", "Medium"],
                    ["Attendance Delay", "Set automated check-in notifications", "Low"]
                ]
            ];
        } elseif (stripos($q, 'overdue') !== false || stripos($q, 'delayed') !== false) {
            $sources = ["tasks", "projects"];
            $overdueCount = $contextData['tasks_summary']['overdue'] ?? 0;
            $direct = "There are currently {$overdueCount} overdue tasks identified in your department workspace.";
            $metrics = [
                "Overdue Tasks: {$overdueCount}",
                "Pending Tasks: " . ($contextData['tasks_summary']['pending'] ?? 0),
                "In Progress Tasks: " . ($contextData['tasks_summary']['in_progress'] ?? 0)
            ];
            $analysis = "Overdue status indicates deliverables that have passed their deadline date without being marked completed.";
            $recs = [
                "Review task deadlines and extend them if resources are loaded.",
                "Verify task dependencies to check for blockages."
            ];

            $vType = "kpi";
            $vData = [
                "headers" => ["Metric", "Value"],
                "rows" => [
                    ["Overdue Tasks", (string)$overdueCount],
                    ["Completion Rate", round(($contextData['tasks_summary']['completed'] / max(1, $contextData['tasks_summary']['total'])) * 100) . "%"]
                ]
            ];
        } elseif (stripos($q, 'burnout') !== false || stripos($q, 'risk') !== false) {
            $sources = ["risk_center", "employees"];
            $risks = $contextData['risks'] ?? [];
            if (!empty($risks)) {
                $names = implode(', ', array_map(fn($r) => $r['employee']['name'] ?? 'Employee', $risks));
                $direct = "We identified risk flags for the following employee(s): {$names}.";
                $metrics = [
                    "Active Risk Alerts: " . count($risks)
                ];
                $analysis = "Risk alerts detect anomalies such as excessively high work hours (burnout risk) or overdue tasks (deadline risk).";
                $recs = [
                    "Conduct review sessions with {$names}.",
                    "Redistribute urgent tasks to underutilized team members."
                ];
                $vType = "table";
                $rows = [];
                foreach ($risks as $r) {
                    $rows[] = [$r['employee']['name'] ?? 'User', strtoupper($r['risk_type']), strtoupper($r['risk_level']), $r['reason']];
                }
                $vData = [
                    "headers" => ["Employee", "Risk Type", "Level", "Reason"],
                    "rows" => $rows
                ];
            } else {
                $direct = "No active risk alerts were found in the database. The team is operating inside normal thresholds.";
            }
        } elseif (stripos($q, 'attendance') !== false || stripos($q, 'absent') !== false) {
            $sources = ["attendance"];
            $isRange = $contextData['attendance_summary']['is_range'] ?? false;
            $periodStr = $isRange 
                ? "during the selected range ({$contextData['attendance_summary']['start_date']} to {$contextData['attendance_summary']['end_date']})" 
                : "today";
            $direct = "Attendance logs summary: " . $contextData['attendance_summary']['present_today'] . " present, " . $contextData['attendance_summary']['late_today'] . " late, and " . $contextData['attendance_summary']['absent_today'] . " absent {$periodStr}.";
            $metrics = [
                ($isRange ? "Present Count: " : "Present Today: ") . $contextData['attendance_summary']['present_today'],
                ($isRange ? "Absent Count: " : "Absent Today: ") . $contextData['attendance_summary']['absent_today']
            ];
            $analysis = "Attendance is tracked using employee clock-in and clock-out event timestamps.";
            $recs = [
                "Analyze absenteeism patterns to identify anomalies.",
            ];
            $vType = "chart";
            $vData = [
                "chart_type" => "pie",
                "chart_labels" => ["Present", "Late", "Absent"],
                "chart_values" => [$contextData['attendance_summary']['present_today'], $contextData['attendance_summary']['late_today'], $contextData['attendance_summary']['absent_today']]
            ];
        } elseif ($contextData['specific_entity']) {
            // Specific employee queried
            $entity = $contextData['specific_entity'];
            $sources = ["employees", "tasks", "attendance", "gitlab_metrics"];
            $direct = "Workspace analysis for employee {$entity['name']}.";
            $m = $entity['metrics'];
            $metrics = [
                "Tasks Completed: " . $m['completed_tasks'],
                "Overdue Tasks: " . $m['overdue_tasks'],
                "Attendance Rate: " . $m['attendance_rate'] . "%",
                "GitLab Commits: " . $m['gitlab_commits']
            ];
            $analysis = "Overall contribution consists of task delivery speed and code push events.";
            $recs = [
                "Review task metrics with the employee in 1:1 check-ins."
            ];
            $vType = "kpi";
            $vData = [
                "headers" => ["Metric", "Value"],
                "rows" => [
                    ["Completed Tasks", (string)$m['completed_tasks']],
                    ["Overdue Tasks", (string)$m['overdue_tasks']],
                    ["Attendance Rate", $m['attendance_rate'] . "%"],
                    ["GitLab Commits", (string)$m['gitlab_commits']]
                ]
            ];
        } else {
            // General query fallback
            $direct = "Hi! I am your AI assistant. Ask me anything about employees, teams, attendance, projects, or tasks, and I'll fetch the answers right away.";
            $metrics = [
                "Total Employees: " . ($contextData['employees_count'] ?? 0),
                "Total Teams: " . ($contextData['teams_count'] ?? 0),
                "Total Projects: " . ($contextData['projects_count'] ?? 0),
            ];
            $analysis = "Telemetry datasets gathered from Live databases: employees, tasks, attendance, gitlab_metrics.";
            $recs = [
                "Try asking specific questions like 'Who is the top performer?' or 'Show overdue tasks'."
            ];
        }

        return [
            'direct_answer' => $direct,
            'supporting_metrics' => $metrics,
            'ai_analysis' => $analysis,
            'recommendations' => $recs,
            'data_sources_used' => $sources,
            'visual_type' => $vType,
            'visual_data' => $vData,
            'engine' => $engineName
        ];
    }

    /**
     * Ask a question and return the answer as a stream of chunks.
     * This supports both Ollama and NVIDIA streaming payloads.
     */
    public function askStream(string $question, array $chatHistory = [], ?string $startDate = null, ?string $endDate = null, callable $onChunk)
    {
        if ($this->isGreeting($question)) {
            $fallback = $this->getGreetingResponse();
            $directAnswer = $fallback['direct_answer'];
            $words = explode(' ', $directAnswer);
            foreach ($words as $i => $word) {
                $onChunk(($i > 0 ? ' ' : '') . $word);
                usleep(25000);
            }
            $onChunk("\n\n[STRUCTURED_METRICS_DATA_JSON]\n" . json_encode($fallback));
            return;
        }

        $user = auth()->user();
        $role = session('active_role', $user->role);

        if (!$startDate) {
            $startDate = Carbon::now()->subDays(7)->toDateString();
        }
        if (!$endDate) {
            $endDate = Carbon::now()->toDateString();
        }

        // 1. Gather live structured data scoped to user permissions
        $contextData = $this->gatherContextData($question, $user, $role, $startDate, $endDate);

        // 2. Fallback check: If AI Service is offline, simulate streaming from the fallback response
        if ($this->isOllamaOffline()) {
            $fallback = $this->buildFallbackResponse($question, $contextData, "AI Service Offline (Local Fallback Mode)");
            $directAnswer = $fallback['direct_answer'];
            // Send in small chunks of words/chars with a tiny sleep to simulate streaming
            $words = explode(' ', $directAnswer);
            foreach ($words as $i => $word) {
                $onChunk(($i > 0 ? ' ' : '') . $word);
                usleep(25000); // 25ms sleep
            }
            // Send the JSON representation of structured data at the end of stream separated by a special marker
            $onChunk("\n\n[STRUCTURED_METRICS_DATA_JSON]\n" . json_encode($fallback));
            return;
        }

        // 3. Construct prompt with compiled database context and memory
        $prompt = $this->compilePrompt($question, $contextData, $chatHistory);

        // 4. Stream response from Ollama/NVIDIA
        try {
            $url = $this->isNvidia 
                ? rtrim($this->baseUrl, '/') . '/chat/completions'
                : rtrim($this->baseUrl, '/') . '/api/chat';

            $headers = [
                'Content-Type' => 'application/json',
            ];
            if ($this->isNvidia) {
                $headers['Authorization'] = 'Bearer ' . $this->apiKey;
            }

            $payload = [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'stream' => true
            ];

            if ($this->isNvidia) {
                $payload['temperature'] = 0.2;
                $payload['top_p'] = 0.7;
                $payload['max_tokens'] = 2048;
                $payload['response_format'] = ['type' => 'json_object'];
            } else {
                $payload['format'] = 'json';
            }

            // Execute raw Guzzle request to read line-by-line streaming chunks
            $client = new \GuzzleHttp\Client();
            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $payload,
                'stream' => true,
                'timeout' => $this->timeout
            ]);

            $body = $response->getBody();
            $buffer = '';
            $accumulatedText = '';

            while (!$body->eof()) {
                $char = $body->read(1);
                if ($char === "\n") {
                    $line = trim($buffer);
                    $buffer = '';

                    if (empty($line)) {
                        continue;
                    }

                    if ($this->isNvidia) {
                        // OpenAI format: "data: {...}"
                        if (str_starts_with($line, 'data:')) {
                            $dataStr = trim(substr($line, 5));
                            if ($dataStr === '[DONE]') {
                                break;
                            }
                            $decoded = json_decode($dataStr, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $text = $decoded['choices'][0]['delta']['content'] ?? '';
                                if ($text !== '') {
                                    $onChunk($text);
                                    $accumulatedText .= $text;
                                }
                            }
                        }
                    } else {
                        // Ollama format: raw JSON lines
                        $decoded = json_decode($line, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $text = $decoded['message']['content'] ?? '';
                            if ($text !== '') {
                                $onChunk($text);
                                $accumulatedText .= $text;
                            }
                            if (!empty($decoded['done'])) {
                                break;
                            }
                        }
                    }
                } else {
                    $buffer .= $char;
                }
            }

            // Try to parse final accumulated text as structured json response
            $structured = json_decode($accumulatedText, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($structured['direct_answer'])) {
                // Send the JSON representation of structured data at the end of stream separated by a special marker
                $onChunk("\n\n[STRUCTURED_METRICS_DATA_JSON]\n" . $accumulatedText);
            } else {
                // If it wasn't valid JSON (e.g. raw text), construct standard wrapper
                $fallback = $this->buildFallbackResponse($question, $contextData, "NVIDIA/Ollama raw output");
                $fallback['direct_answer'] = $accumulatedText ?: 'No response from AI model.';
                $onChunk("\n\n[STRUCTURED_METRICS_DATA_JSON]\n" . json_encode($fallback));
            }

        } catch (Exception $e) {
            Log::warning("AI Chat Streaming Exception: " . $e->getMessage() . ". Falling back to rule-based engine.");
            $fallback = $this->buildFallbackResponse($question, $contextData, "AI Fallback Engine");
            $directAnswer = $fallback['direct_answer'];
            $words = explode(' ', $directAnswer);
            foreach ($words as $i => $word) {
                $onChunk(($i > 0 ? ' ' : '') . $word);
                usleep(25000);
            }
            $onChunk("\n\n[STRUCTURED_METRICS_DATA_JSON]\n" . json_encode($fallback));
        }
    }
}
