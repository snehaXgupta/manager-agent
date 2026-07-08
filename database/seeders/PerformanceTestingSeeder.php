<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class PerformanceTestingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign keys and truncate existing tables to ensure clean count tests
        Schema::disableForeignKeyConstraints();
        DB::table('employee_skill')->truncate();
        DB::table('skills')->truncate();
        DB::table('designations')->truncate();
        DB::table('departments')->truncate();
        DB::table('notifications')->truncate();
        DB::table('risk_alerts')->truncate();
        DB::table('performance_reports')->truncate();
        DB::table('time_entries')->truncate();
        DB::table('attendance_logs')->truncate();
        DB::table('tasks')->truncate();
        DB::table('team_user')->truncate();
        DB::table('teams')->truncate();
        DB::table('project_members')->truncate();
        DB::table('projects')->truncate();
        DB::table('meetings')->truncate(); // clear meetings too
        DB::table('developer_tokens')->truncate();
        DB::table('developer_activities')->truncate();
        DB::table('users')->truncate();
        Schema::enableForeignKeyConstraints();

        // Seed Lookup Data
        $departmentsData = [
            ['name' => 'Engineering', 'description' => 'Software engineering and development team.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Product Management', 'description' => 'Product definition, roadmaps, and lifecycle.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Design & UX', 'description' => 'User experience and visual brand design.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'QA & Testing', 'description' => 'Quality assurance and software testing.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Marketing & Growth', 'description' => 'Marketing campaigns and user acquisition.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ];
        DB::table('departments')->insert($departmentsData);

        $designationsData = [
            ['name' => 'Software Engineer', 'description' => 'Builds software products.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Senior Developer', 'description' => 'Architects and writes complex systems.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Team Lead', 'description' => 'Leads a small team of engineers.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Product Manager', 'description' => 'Defines product features and lifecycle.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'QA Engineer', 'description' => 'Ensures code quality and writes tests.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'UI/UX Designer', 'description' => 'Creates user interface mockups.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ];
        DB::table('designations')->insert($designationsData);

        $skillsData = [
            ['name' => 'PHP', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Laravel', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Vue.js', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'MySQL', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Git', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Docker', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'System Design', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'HTML & CSS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'JavaScript', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Node.js', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ];
        DB::table('skills')->insert($skillsData);

        // Realistic seed sources
        $firstNames = ['John', 'Jane', 'Alex', 'Emily', 'Michael', 'Sarah', 'David', 'Jessica', 'James', 'Ashley', 'Robert', 'Amanda', 'Johnathan', 'Megan', 'William', 'Stephanie', 'Joseph', 'Nicole', 'Andrew', 'Elizabeth', 'Ryan', 'Kathryn', 'Brandon', 'Rachel', 'Justin', 'Christine', 'Matthew', 'Samantha', 'Daniel', 'Lauren', 'Christopher', 'Amber', 'Nicholas', 'Brittany', 'Joshua', 'Danielle', 'Taylor', 'Heather', 'Tyler', 'Melissa', 'Kayla', 'Victoria', 'Zachary', 'Kyle', 'Tiffany', 'Jacob', 'Alyssa', 'Ethan', 'Courtney', 'Ben', 'Sofia', 'Liam', 'Olivia', 'Noah', 'Emma', 'Oliver', 'Ava', 'Elijah', 'Isabella', 'Leo', 'Mia', 'Lucas', 'Charlotte', 'Mason', 'Amelia', 'Logan', 'Harper', 'Alexander', 'Evelyn', 'Abigail', 'Grace', 'Chloe', 'Carter', 'Camila', 'Owen', 'Penelope', 'Wyatt', 'Riley', 'Layla', 'Jack', 'Lillian', 'Luke', 'Nora', 'Zoey', 'Grayson', 'Mila'];
        $lastNames = ['Smith', 'Johnson', 'Middleton', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores', 'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts', 'Gomez', 'Phillips', 'Evans', 'Turner', 'Diaz', 'Parker', 'Cruz', 'Edwards', 'Collins', 'Reyes', 'Stewart', 'Morris', 'Morales', 'Murphy', 'Cook', 'Rogers', 'Gutierrez', 'Ortiz', 'Morgan', 'Cooper', 'Peterson', 'Bailey', 'Reed', 'Kelly', 'Howard', 'Ramos', 'Kim', 'Cox', 'Ward', 'Richardson', 'Watson', 'Brooks', 'Chavez', 'Wood', 'James', 'Bennett', 'Gray', 'Mendoza', 'Ruiz', 'Hughes', 'Price', 'Alvarez', 'Castillo', 'Sanders', 'Patel', 'Myers', 'Long', 'Ross', 'Foster', 'Jimenez'];
        
        $projectNames = ['Phoenix', 'Apollo', 'Titan', 'Zeus', 'Aurora', 'Genesis', 'Orion', 'Nova', 'Voyager', 'Pulse', 'Oasis', 'Summit', 'Spire', 'Eclipse', 'Nexus', 'Horizon', 'Vanguard', 'Alpha', 'Beta', 'Gamma', 'Delta', 'Omega', 'Sync', 'Flow', 'Optima', 'Prism', 'Quantum', 'Helix', 'Velocity', 'Sentinel', 'Catalyst', 'Apex', 'Core', 'Meridian', 'Zenith', 'Echo', 'Vector', 'Infinity', 'Matrix', 'Atlas', 'Solstice', 'Nebula', 'Chronos', 'Aegis', 'Fortress', 'Beacon', 'Sentry', 'Stellar', 'Galactic', 'Cosmic'];
        $projectNameSuffixes = ['System', 'Platform', 'Engine', 'Framework', 'API', 'Gateway', 'Dashboard', 'Service', 'Pipeline', 'Integration', 'Analyzer', 'Toolkit', 'Module', 'Component'];

        $commitMessages = ['Fix bug in auth middleware', 'Update task duration calculations', 'Refactor database seeder logic', 'Add index on user_id column', 'Implement chunked batch inserts', 'Fix N+1 query loops in dashboard', 'Improve team consistency score formula', 'Update performance report layout', 'Fix alignment in access token list', 'Add clipboard copy helper to API keys', 'Configure Redis cache for leaderboard', 'Write unit tests for webhook signature validation', 'Optimize predictive workload algorithm', 'Fix checkout status mapping in logs', 'Update Laravel environment configurations', 'Clean up console log and debug print statements', 'Improve SEO meta tags and description', 'Update team user relation constraints', 'Refactor risk center alert counts', 'Fix daily attendance status filter'];
        $taskTitles = ['Implement OAuth integration', 'Refactor query logic', 'Optimize index speeds', 'Write dashboard tests', 'Design settings panel', 'Fix layout overflow', 'Secure API keys storage', 'Track commit history', 'Review pull requests', 'Configure server logs', 'Clean database records', 'Update attendance logs', 'Setup notifications service', 'Document API endpoints', 'Fix css variables', 'Implement dark mode', 'Improve site loading time', 'Verify signature validation', 'Calculate productivity scores', 'Report team health stats'];

        $startTs = Carbon::now()->subDays(20)->timestamp;
        $endTs = Carbon::now()->timestamp;

        // 1. Create a System Admin
        $password = Hash::make('password');
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Joseph Cooper',
            'email' => 'admin@gmail.com',
            'password' => $password,
            'role' => 'admin',
            'created_at' => Carbon::now()->subDays(25),
            'updated_at' => Carbon::now()->subDays(25),
        ]);

        // 2. Create 5 Managers
        $managers = [];
        $managerNames = ["Amelia Brand", "Murphy Cooper", "Professor Brand", "Dr. Mann", "Romilly"];
        foreach ($managerNames as $idx => $name) {
            $managers[] = [
                'id' => $idx + 2,
                'name' => $name,
                'email' => "manager-" . ($idx + 1) . "@company.com",
                'password' => $password,
                'role' => 'manager',
                'manager_id' => null,
                'created_at' => Carbon::now()->subDays(25),
                'updated_at' => Carbon::now()->subDays(25),
            ];
        }
        DB::table('users')->insert($managers);
        $managerIds = range(2, 6);

        // 2.5 Create 5 Team Leads (IDs 7 to 11)
        $teamLeads = [];
        for ($i = 1; $i <= 5; $i++) {
            $teamLeads[] = [
                'id' => 6 + $i,
                'name' => "Team Lead " . $i,
                'email' => "lead-{$i}@company.com",
                'password' => $password,
                'role' => 'team_lead',
                'manager_id' => 2, // reports to Amelia Brand
                'department_id' => 1, // Engineering
                'designation_id' => 3, // Team Lead
                'created_at' => Carbon::now()->subDays(25),
                'updated_at' => Carbon::now()->subDays(25),
            ];
        }
        DB::table('users')->insert($teamLeads);

        // 3. Create 50 Employees
        $employeeIds = range(12, 61);
        $employeesChunk = [];
        $employeeSkills = [];

        for ($i = 1; $i <= 50; $i++) {
            $empId = 11 + $i;
            if ($i === 1) {
                $name = "Charlotte Murphy 1";
                $email = "charlotte.murphy.1@company.com";
            } else {
                $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)] . ' ' . $i;
                $email = strtolower(str_replace(' ', '.', $name)) . '@company.com';
            }

            // Assign reporting manager:
            // All 50 employees report to Manager 1 (Amelia Brand, ID 2)
            $supervisorId = 2;

            $employeesChunk[] = [
                'id' => $empId,
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => 'employee',
                'manager_id' => $supervisorId,
                'department_id' => rand(1, 5),
                'designation_id' => rand(1, 6),
                'github_username' => strtolower(explode(' ', $name)[0]) . $i . '_git',
                'gitlab_username' => strtolower(explode(' ', $name)[0]) . $i . '_lab',
                'bitbucket_username' => strtolower(explode(' ', $name)[0]) . $i . '_bit',
                'created_at' => Carbon::now()->subDays(25),
                'updated_at' => Carbon::now()->subDays(25),
            ];

            // Generate 2-4 random skills
            $skillCount = rand(2, 4);
            $chosenSkills = (array) array_rand(range(1, 10), $skillCount);
            foreach ($chosenSkills as $sIdx) {
                $employeeSkills[] = [
                    'user_id' => $empId,
                    'skill_id' => $sIdx + 1,
                    'proficiency' => rand(1, 5),
                    'created_at' => Carbon::now()->subDays(25),
                    'updated_at' => Carbon::now()->subDays(25),
                ];
            }
        }
        DB::table('users')->insert($employeesChunk);
        DB::table('employee_skill')->insert($employeeSkills);

        // 4. Create 25 Projects
        $projects = [];
        for ($i = 1; $i <= 25; $i++) {
            $projectId = $i;
            $name = $projectNames[array_rand($projectNames)] . ' Project ' . $i;
            $projects[] = [
                'id' => $projectId,
                'name' => $name,
                'description' => "Detailed specifications and scope for " . $name,
                'manager_id' => 2, // Assign all projects to Manager 1 (ID 2)
                'created_at' => Carbon::now()->subDays(25),
                'updated_at' => Carbon::now()->subDays(25),
            ];
        }
        DB::table('projects')->insert($projects);

        // 5. Create Project User Pivot Records (assigning 50 employees to 25 projects)
        $projectUserChunk = [];
        $puId = 1;
        for ($i = 1; $i <= 50; $i++) {
            $empId = $employeeIds[$i - 1];
            // Assign primary project (modulo 25)
            $primaryProjectId = (($empId - 12) % 25) + 1;
            $projectUserChunk[] = [
                'id' => $puId++,
                'project_id' => $primaryProjectId,
                'user_id' => $empId,
                'created_at' => Carbon::now()->subDays(25),
                'updated_at' => Carbon::now()->subDays(25),
            ];

            // Add a secondary project to some employees (e.g. even IDs)
            if ($empId % 2 == 0) {
                $secondaryProjectId = (($primaryProjectId + 12) % 25) + 1;
                $projectUserChunk[] = [
                    'id' => $puId++,
                    'project_id' => $secondaryProjectId,
                    'user_id' => $empId,
                    'created_at' => Carbon::now()->subDays(25),
                    'updated_at' => Carbon::now()->subDays(25),
                ];
            }
        }
        DB::table('project_members')->insert($projectUserChunk);

        // 6. Create 5 Teams
        $teams = [];
        for ($i = 1; $i <= 5; $i++) {
            $teamId = $i;
            $name = $projectNames[array_rand($projectNames)] . ' ' . $projectNameSuffixes[array_rand($projectNameSuffixes)] . ' ' . $i;
            $teams[] = [
                'id' => $teamId,
                'name' => $name,
                'manager_id' => 2, // Assign all teams to Manager 1 (ID 2)
                'created_at' => Carbon::now()->subDays(25),
                'updated_at' => Carbon::now()->subDays(25),
            ];
        }
        DB::table('teams')->insert($teams);

        // 7. Create Team User Pivot Records
        $teamUserChunk = [];
        $tuId = 1;
        for ($i = 1; $i <= 50; $i++) {
            $empId = $employeeIds[$i - 1];
            // Assign primary team (modulo 5)
            $primaryTeamId = (($empId - 12) % 5) + 1;
            $teamUserChunk[] = [
                'id' => $tuId++,
                'team_id' => $primaryTeamId,
                'user_id' => $empId,
                'created_at' => Carbon::now()->subDays(25),
                'updated_at' => Carbon::now()->subDays(25),
            ];
            
            if ($empId % 2 == 0) {
                $secondaryTeamId = (($primaryTeamId + 2) % 5) + 1;
                $teamUserChunk[] = [
                    'id' => $tuId++,
                    'team_id' => $secondaryTeamId,
                    'user_id' => $empId,
                    'created_at' => Carbon::now()->subDays(25),
                    'updated_at' => Carbon::now()->subDays(25),
                ];
            }
        }
        DB::table('team_user')->insert($teamUserChunk);

        // 8. Create 250 Tasks between 1 June – 18 June
        $tasksChunk = [];
        $taskReferenceData = [];
        
        for ($i = 1; $i <= 250; $i++) {
            $empId = $employeeIds[($i - 1) % count($employeeIds)];
            $teamId = (($empId - 12) % 5) + 1;
            $projectId = (($empId - 12) % 25) + 1;
            
            $createdTs = rand($startTs, $endTs - (4 * 24 * 3600)); // created before June 14
            $createdAt = Carbon::createFromTimestamp($createdTs);
            
            $empIndex = ($i - 1) % 50;
            $taskNum = intval(($i - 1) / 50);

            $isActive = false;
            if ($empIndex % 10 < 3) {
                // Underutilized: 1 active task
                $isActive = ($taskNum === 0);
            } elseif ($empIndex % 10 < 9) {
                // Balanced: 3 active tasks
                $isActive = ($taskNum < 3);
            } else {
                // Overloaded: 5 active tasks
                $isActive = true;
            }

            if ($isActive) {
                if ($taskNum === 0 && $empIndex < 40) {
                    $status = 'in_progress';
                } else {
                    $status = ($i % 4 == 0) ? 'pending' : 'in_progress';
                }
            } else {
                $status = 'completed';
            }

            $deadlineAt = (clone $createdAt)->addDays(rand(1, 5));
            
            if ($status === 'completed') {
                $completedAt = (clone $createdAt)->addHours(rand(12, 120)); // some will exceed deadline
                $updatedAt = $completedAt;
            } else {
                $updatedAt = (clone $createdAt)->addHours(rand(1, 24));
            }

            $tasksChunk[] = [
                'id' => $i,
                'title' => $taskTitles[array_rand($taskTitles)] . " " . $i,
                'description' => "Detailed specifications for task reference " . $i,
                'status' => $status,
                'deadline' => $deadlineAt,
                'assigned_to' => $empId,
                'team_id' => $teamId,
                'project_id' => $projectId,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];

            // Save first task per employee for time entries
            if ($i <= 50) {
                $taskReferenceData[] = [
                    'id' => $i,
                    'assigned_to' => $empId,
                    'created_at' => $createdAt,
                ];
            }
        }
        DB::table('tasks')->insert($tasksChunk);

        // 9. Create Attendance Logs (daily records including target date)
        $attendanceLogsChunk = [];
        $attId = 1;
        $targetDate = Carbon::today();

        $teamMemberIds = array_merge(range(7, 11), $employeeIds);
        foreach ($teamMemberIds as $index => $empId) {
            $i = $index + 1;
            
            // 25/55 team members present today.
            if ($i <= 25) {
                $status = 'present';
                $checkIn = '09:00:00';
                $checkOut = '17:00:00';
            } else {
                $status = 'absent';
                $checkIn = '00:00:00';
                $checkOut = '00:00:00';
            }

            $attendanceLogsChunk[] = [
                'id' => $attId++,
                'user_id' => $empId,
                'date' => $targetDate->toDateString(),
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => $status,
                'created_at' => $targetDate->copy()->hour(9),
                'updated_at' => $targetDate->copy()->hour(17),
            ];
        }
        DB::table('attendance_logs')->insert($attendanceLogsChunk);

        // 10. Create 50 Time Entries on tasks matching assignees
        $timeEntriesChunk = [];
        for ($i = 1; $i <= 50; $i++) {
            $refTask = $taskReferenceData[$i - 1];
            $startedAt = (clone $refTask['created_at'])->addHours(rand(1, 4));
            $duration = rand(3600, 28800); // 1 to 8 hours
            $stoppedAt = (clone $startedAt)->addSeconds($duration);

            $timeEntriesChunk[] = [
                'id' => $i,
                'task_id' => $refTask['id'],
                'user_id' => $refTask['assigned_to'],
                'started_at' => $startedAt,
                'stopped_at' => $stoppedAt,
                'duration_seconds' => $duration,
                'created_at' => $startedAt,
                'updated_at' => $stoppedAt,
            ];
        }
        DB::table('time_entries')->insert($timeEntriesChunk);

        // 10.5 Create active Time Entries (clocked-in) for today's present working employees
        $activeTimeEntriesChunk = [];
        $activeTimeEntryId = 10001;

        // The first 25 employees are present today
        for ($i = 1; $i <= 25; $i++) {
            $empId = $employeeIds[$i - 1];
            $activeTimeEntriesChunk[] = [
                'id' => $activeTimeEntryId++,
                'task_id' => $i,
                'user_id' => $empId,
                'started_at' => $targetDate->copy()->hour(9)->minute(0)->second(0),
                'stopped_at' => null,
                'duration_seconds' => 0,
                'created_at' => $targetDate->copy()->hour(9)->minute(0)->second(0),
                'updated_at' => $targetDate->copy()->hour(9)->minute(0)->second(0),
            ];
        }
        DB::table('time_entries')->insert($activeTimeEntriesChunk);

        $taskReferenceData = []; // clear memory reference

        // 11. Create Commits (1 per project/repo per day for 25 repos over 23 days)
        $commitsChunk = [];
        $commitId = 1;
        for ($repoId = 1; $repoId <= 25; $repoId++) {
            $repoName = "org/repo-{$repoId}";
            for ($day = 1; $day <= 23; $day++) {
                $date = Carbon::now()->subDays(23 - $day)->hour(rand(9, 18))->minute(rand(0, 59))->second(rand(0, 59));
                $empId = $employeeIds[($repoId + $day) % count($employeeIds)];

                $commitsChunk[] = [
                    'id' => $commitId++,
                    'user_id' => $empId,
                    'platform' => $repoId % 3 == 0 ? 'github' : ($repoId % 3 == 1 ? 'gitlab' : 'bitbucket'),
                    'event_type' => 'commit',
                    'repository' => $repoName,
                    'reference_id' => bin2hex(random_bytes(20)),
                    'details_json' => json_encode([
                        'commit_message' => $commitMessages[($repoId + $day) % count($commitMessages)],
                        'additions' => rand(10, 200),
                        'deletions' => rand(5, 50),
                    ]),
                    'occurred_at' => $date,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
            }
        }
        DB::table('developer_activities')->insert($commitsChunk);

        // 12. Create Pull Requests
        $prsChunk = [];
        $prId = $commitId;
        for ($i = 1; $i <= 25; $i++) {
            $empId = $employeeIds[rand(0, count($employeeIds) - 1)];
            $repoId = $i;
            $repoName = "org/repo-{$repoId}";
            $date = Carbon::now()->subDays(rand(1, 23))->hour(rand(9, 18))->minute(rand(0, 59));
            $eventType = $i % 2 == 0 ? 'pr_opened' : 'pr_merged';

            $prsChunk[] = [
                'id' => $prId++,
                'user_id' => $empId,
                'platform' => $repoId % 3 == 0 ? 'github' : ($repoId % 3 == 1 ? 'gitlab' : 'bitbucket'),
                'event_type' => $eventType,
                'repository' => $repoName,
                'reference_id' => (string) $i,
                'details_json' => json_encode([
                    'pr_title' => "Feature implementation request " . $i,
                    'source_branch' => "feature/branch-" . $i,
                    'target_branch' => "main",
                    'additions' => rand(50, 500),
                    'deletions' => rand(10, 100),
                ]),
                'occurred_at' => $date,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }
        DB::table('developer_activities')->insert($prsChunk);
        $prsChunk = [];

        // 13. Create Code Reviews
        $reviewsChunk = [];
        for ($i = 1; $i <= 25; $i++) {
            $empId = $employeeIds[rand(0, count($employeeIds) - 1)];
            $repoId = rand(1, 25);
            $repoName = "org/repo-{$repoId}";
            $date = Carbon::now()->subDays(rand(1, 23))->hour(rand(9, 18))->minute(rand(0, 59));

            $reviewsChunk[] = [
                'id' => $prId++,
                'user_id' => $empId,
                'platform' => $repoId % 3 == 0 ? 'github' : ($repoId % 3 == 1 ? 'gitlab' : 'bitbucket'),
                'event_type' => 'review_submitted',
                'repository' => $repoName,
                'reference_id' => (string) rand(1, 25),
                'details_json' => json_encode([
                    'review_state' => $i % 4 == 0 ? 'changes_requested' : 'approved',
                    'comments_count' => rand(1, 10),
                ]),
                'occurred_at' => $date,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }
        DB::table('developer_activities')->insert($reviewsChunk);
        $reviewsChunk = [];

        // 14. Create Performance Reports (2 per manager)
        $analyticsService = app(\App\Services\PerformanceAnalyticsService::class);
        $reportsChunk = [];
        $repId = 1;
        foreach ($managerIds as $mgrId) {
            // Report for Week 1 (last week 14-8 days ago)
            $start1 = Carbon::now()->subDays(14)->startOfDay();
            $end1 = Carbon::now()->subDays(8)->endOfDay();
            $metrics1 = $analyticsService->calculateTeamMetrics($mgrId, $start1, $end1);

            $reportsChunk[] = [
                'id' => $repId++,
                'manager_id' => $mgrId,
                'report_type' => 'weekly',
                'period_start' => $start1->toDateString(),
                'period_end' => $end1->toDateString(),
                'metrics_json' => json_encode($metrics1),
                'ai_insights_json' => json_encode([
                    'summary' => "Overall solid team delivery.",
                    'strengths' => ["Consistent hours logged.", "Good collaboration."],
                    'weaknesses' => ["Some tasks delayed."],
                    'risks' => ["Slight overload potential."],
                    'recommendations' => ["Balance tasks next sprint."],
                ]),
                'manager_score' => $metrics1['manager_score'],
                'generated_at' => $end1->copy()->hour(23)->minute(59)->second(59),
                'created_at' => $end1,
                'updated_at' => $end1,
            ];

            // Report for Week 2 (last 7 days)
            $start2 = Carbon::now()->subDays(7)->startOfDay();
            $end2 = Carbon::now()->endOfDay();
            $metrics2 = $analyticsService->calculateTeamMetrics($mgrId, $start2, $end2);

            $reportsChunk[] = [
                'id' => $repId++,
                'manager_id' => $mgrId,
                'report_type' => 'weekly',
                'period_start' => $start2->toDateString(),
                'period_end' => $end2->toDateString(),
                'metrics_json' => json_encode($metrics2),
                'ai_insights_json' => json_encode([
                    'summary' => "Strong improvement in sprint deliverables.",
                    'strengths' => ["Excellent speed.", "Higher task completion."],
                    'weaknesses' => [],
                    'risks' => [],
                    'recommendations' => ["Maintain current sprint pacing."],
                ]),
                'manager_score' => $metrics2['manager_score'],
                'generated_at' => $end2->copy()->hour(23)->minute(59)->second(59),
                'created_at' => $end2,
                'updated_at' => $end2,
            ];
        }
        DB::table('performance_reports')->insert($reportsChunk);
        $reportsChunk = [];

        // 15. Create Risk Alerts
        $riskAlerts = [];
        for ($i = 1; $i <= 5; $i++) {
            $empId = $employeeIds[$i - 1];
            $date = Carbon::now()->subDays(rand(1, 20));
            
            $riskAlerts[] = [
                'id' => $i,
                'employee_id' => $empId,
                'risk_level' => $i % 3 == 0 ? 'high' : ($i % 3 == 1 ? 'medium' : 'low'),
                'risk_type' => $i % 2 == 0 ? 'burnout' : 'deadline',
                'reason' => $i % 2 == 0 ? "Employee logged over 50 hours in a week." : "Task delivery approaching deadline without commits.",
                'metrics_json' => json_encode(['hours_logged' => rand(45, 60)]),
                'detected_at' => $date,
                'is_resolved' => false,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }
        DB::table('risk_alerts')->insert($riskAlerts);
        $riskAlerts = [];

        // 16. Create Notifications
        $notifications = [];
        for ($i = 1; $i <= 5; $i++) {
            $date = Carbon::now()->subDays(rand(1, 20));

            $notifications[] = [
                'id' => $i,
                'user_id' => 2, // Route notifications to Manager 1 (ID 2)
                'type' => $i % 2 == 0 ? 'burnout_risk' : 'deadline_risk',
                'severity' => $i % 3 == 0 ? 'CRITICAL' : ($i % 3 == 1 ? 'WARNING' : 'INFO'),
                'title' => $i % 2 == 0 ? "Burnout Risk: High Workload" : "Deadline Alert: Delivery Slipping",
                'message' => "An automated scan detected potential alert flags on your team.",
                'is_read' => false,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }
        DB::table('notifications')->insert($notifications);
        $notifications = [];
    }
}
