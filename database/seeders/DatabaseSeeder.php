<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\AttendanceLog;
use App\Models\PerformanceReport;
use App\Models\RiskAlert;
use App\Models\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PerformanceTestingSeeder::class);
        return;

        // 0. Create an Admin
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // 1. Create Managers
        $manager = User::create([
            'name' => 'Sarah',
            'email' => 'sarah@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $sneha = User::create([
            'name' => 'Sneha',
            'email' => 'sneha@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        // 2. Create Employees reporting to Sarah
        $rahul = User::create([
            'name' => 'Rahul',
            'email' => 'rahul@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);

        $arjun = User::create([
            'name' => 'Arjun',
            'email' => 'arjun@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);

        $shipra = User::create([
            'name' => 'Shipra',
            'email' => 'shipra@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);

        // 2.5 Create Employee reporting to Sneha
        $priya = User::create([
            'name' => 'Priya',
            'email' => 'priya@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $sneha->id,
        ]);

        // 3. Seed Tasks
        // Rahul's tasks: 2 completed (1 on time, 1 late), 1 pending
        Task::create([
            'title' => 'API Endpoint Integration',
            'description' => 'Integrate task timer endpoints.',
            'status' => 'in_progress',
            'deadline' => Carbon::now()->subDays(2),
            'assigned_to' => $rahul->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);

        Task::create([
            'title' => 'Database Schema Migration',
            'description' => 'Write migrations for activity logs.',
            'status' => 'completed',
            'deadline' => Carbon::now()->subDays(4),
            'assigned_to' => $rahul->id,
            'created_at' => Carbon::now()->subDays(6),
            'updated_at' => Carbon::now()->subDays(3),
        ]);

        Task::create([
            'title' => 'Refactor Frontend Layout',
            'description' => 'Fix CSS alignments.',
            'status' => 'pending',
            'deadline' => Carbon::now()->addDays(2),
            'assigned_to' => $rahul->id,
            'created_at' => Carbon::now()->subDays(1),
        ]);

        // Arjun's tasks: 2 completed on time
        Task::create([
            'title' => 'Write Unit Tests',
            'description' => 'Unit tests for analytics calculations.',
            'status' => 'completed',
            'deadline' => Carbon::now()->subDays(1),
            'assigned_to' => $arjun->id,
            'created_at' => Carbon::now()->subDays(4),
            'updated_at' => Carbon::now()->subDays(2),
        ]);

        Task::create([
            'title' => 'Configure Redis Cache',
            'description' => 'Setup cache drivers.',
            'status' => 'completed',
            'deadline' => Carbon::now()->addDays(5),
            'assigned_to' => $arjun->id,
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(1),
        ]);

        // Shipra's tasks: 1 completed on time, 1 overdue in-progress
        Task::create([
            'title' => 'Document API Routes',
            'description' => 'Write API specification.',
            'status' => 'completed',
            'deadline' => Carbon::now()->addDays(1),
            'assigned_to' => $shipra->id,
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(1),
        ]);

        Task::create([
            'title' => 'Client Delivery Review',
            'description' => 'Prepare presentation slides.',
            'status' => 'in_progress',
            'deadline' => Carbon::now()->subDays(1),
            'assigned_to' => $shipra->id,
            'created_at' => Carbon::now()->subDays(3),
        ]);

        // Priya's tasks: 1 completed on time, 1 pending
        Task::create([
            'title' => 'Design System Implementation',
            'description' => 'Build UI components.',
            'status' => 'completed',
            'deadline' => Carbon::now()->addDays(2),
            'assigned_to' => $priya->id,
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(1),
        ]);

        Task::create([
            'title' => 'Code Review and QA',
            'description' => 'Verify login flows.',
            'status' => 'pending',
            'deadline' => Carbon::now()->addDays(5),
            'assigned_to' => $priya->id,
            'created_at' => Carbon::now()->subDays(1),
        ]);

        // 4. Seed Time Entries & Attendance Logs for the past 7 days
        $employees = [$rahul, $arjun, $shipra, $priya];
        $hoursMap = [
            $rahul->id => 6,  // Underworks slightly
            $arjun->id => 8,  // Works standard expected
            $shipra->id => 4, // Underworks significantly
            $priya->id => 7,  // Works standard
        ];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            if (!$date->isWeekend()) {
                foreach ($employees as $employee) {
                    $hours = $hoursMap[$employee->id];
                    $task = Task::where('assigned_to', $employee->id)->first();

                    TimeEntry::create([
                        'task_id' => $task->id,
                        'user_id' => $employee->id,
                        'started_at' => $date->copy()->hour(9)->minute(0)->second(0),
                        'stopped_at' => $date->copy()->hour(9 + $hours)->minute(0)->second(0),
                        'duration_seconds' => $hours * 3600,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);

                    AttendanceLog::create([
                        'user_id' => $employee->id,
                        'date' => $date->toDateString(),
                        'check_in' => '09:00:00',
                        'check_out' => '17:00:00',
                        'status' => 'present',
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }
        }

        // 5. Seed Performance Reports
        // Sarah's previous week report
        PerformanceReport::create([
            'manager_id' => $manager->id,
            'report_type' => 'weekly',
            'period_start' => Carbon::now()->subDays(13)->startOfDay(),
            'period_end' => Carbon::now()->subDays(7)->endOfDay(),
            'metrics_json' => [
                'team_size' => 3,
                'task_completion_rate' => 75.0,
                'deadline_adherence_rate' => 66.7,
                'productivity_score' => 70.0,
                'consistency_score' => 75.0,
                'manager_score' => 71.7,
                'metrics_breakdown' => [
                    'total_assigned_tasks' => 4,
                    'completed_tasks' => 3,
                    'completed_on_time_tasks' => 2,
                    'total_hours_logged' => 90,
                    'expected_hours' => 120,
                ]
            ],
            'ai_insights_json' => [
                'summary' => 'The team demonstrated moderate performance during this historical period. While task delivery was stable, deadline adherence fell short due to bottleneck issues on critical frontend tasks.',
                'strengths' => [
                    'Good hours logging consistency across the team.',
                    'Solid communication pathways established.'
                ],
                'weaknesses' => [
                    'Lower task completion rate due to blocked items.',
                    'Productivity slightly below baseline expectations.'
                ],
                'risks' => [
                    'Fatigue risks if team attempts to recover delayed milestones in the next sprint.'
                ],
                'recommendations' => [
                    'Hold a retro to find bottlenecks.',
                    'Review complexity estimates of backlog tasks.'
                ],
                'team_health' => 'Healthy but fatigued'
            ],
            'manager_score' => 71.7,
            'generated_at' => Carbon::now()->subDays(7),
        ]);

        // Sarah's current week report
        PerformanceReport::create([
            'manager_id' => $manager->id,
            'report_type' => 'weekly',
            'period_start' => Carbon::now()->subDays(6)->startOfDay(),
            'period_end' => Carbon::now()->endOfDay(),
            'metrics_json' => [
                'team_size' => 3,
                'task_completion_rate' => 85.0,
                'deadline_adherence_rate' => 80.0,
                'productivity_score' => 85.0,
                'consistency_score' => 80.0,
                'manager_score' => 82.5,
                'metrics_breakdown' => [
                    'total_assigned_tasks' => 6,
                    'completed_tasks' => 5,
                    'completed_on_time_tasks' => 4,
                    'total_hours_logged' => 120,
                    'expected_hours' => 120,
                ]
            ],
            'ai_insights_json' => [
                'summary' => 'Outstanding performance improvement this week. Most deliverables were completed on schedule, and overall developer productivity shows positive trajectory.',
                'strengths' => [
                    'Outstanding team-wide hours logging matching expected targets.',
                    'Improved task completion and faster turnaround.'
                ],
                'weaknesses' => [
                    'Slight variance in daily attendance logs for individual members.'
                ],
                'risks' => [
                    'Minor risk of workload bottleneck in upcoming sprint releases.'
                ],
                'recommendations' => [
                    'Celebrate recent accomplishments to boost team morale.',
                    'Ensure task division keeps workload balanced.'
                ],
                'team_health' => 'Excellent'
            ],
            'manager_score' => 82.5,
            'generated_at' => Carbon::now(),
        ]);

        // 6. Seed Risk Alerts for Sarah's employees
        // Rahul burnout risk
        RiskAlert::create([
            'employee_id' => $rahul->id,
            'risk_level' => 'high',
            'risk_type' => 'burnout',
            'reason' => "Rahul logged 52 hours in the last 7 days (averaging 10.4 hours/day), presenting a high risk of burnout.",
            'metrics_json' => ['total_hours' => 52, 'avg_daily_hours' => 10.4],
            'detected_at' => Carbon::now()->subDays(2),
        ]);

        // Rahul deadline risk
        RiskAlert::create([
            'employee_id' => $rahul->id,
            'risk_level' => 'medium',
            'risk_type' => 'deadline',
            'reason' => "API integration delayed by 2 days",
            'metrics_json' => ['overdue_tasks' => 1, 'completion_rate' => 50.0],
            'detected_at' => Carbon::now()->subDays(2),
        ]);

        // Shipra deadline risk
        RiskAlert::create([
            'employee_id' => $shipra->id,
            'risk_level' => 'medium',
            'risk_type' => 'deadline',
            'reason' => "Client delivery may slip",
            'metrics_json' => ['overdue_tasks' => 1, 'completion_rate' => 66.7],
            'detected_at' => Carbon::now()->subDay(),
        ]);

        // 7. Seed Notifications for manager Sarah
        Notification::create([
            'user_id' => $manager->id,
            'type' => 'burnout_risk',
            'severity' => 'CRITICAL',
            'title' => 'Burnout Risk Alert: Rahul',
            'message' => 'Rahul logged 52 hours in the last 7 days (averaging 10.4 hours/day), presenting a high risk of burnout.',
            'is_read' => false,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        Notification::create([
            'user_id' => $manager->id,
            'type' => 'deadline_risk',
            'severity' => 'WARNING',
            'title' => 'Deadline Risk Alert: Rahul',
            'message' => 'API integration delayed by 2 days',
            'is_read' => false,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        Notification::create([
            'user_id' => $manager->id,
            'type' => 'deadline_risk',
            'severity' => 'WARNING',
            'title' => 'Deadline Risk Alert: Shipra',
            'message' => 'Client delivery may slip',
            'is_read' => false,
            'created_at' => Carbon::now()->subDay(),
        ]);

        Notification::create([
            'user_id' => $manager->id,
            'type' => 'ai_recommendation',
            'severity' => 'INFO',
            'title' => 'New AI Recommendations Available',
            'message' => 'A new predictive workload report is ready with resources balancing suggestions for your team.',
            'is_read' => false,
            'created_at' => Carbon::now(),
        ]);
    }
}
