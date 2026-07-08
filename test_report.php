<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ReportService;
use App\Models\User;
use App\Models\AttendanceLog;
use Illuminate\Support\Carbon;

$managerId = 2; // Amelia Brand
$manager = User::find($managerId);

if (!$manager) {
    echo "Manager with ID {$managerId} not found.\n";
    exit(1);
}

echo "Manager: {$manager->name} ({$manager->email})\n";

// Count today's attendance for the team
$teamUserSubquery = User::select('id')->where('manager_id', $managerId);
$attendanceToday = AttendanceLog::whereIn('user_id', $teamUserSubquery)
    ->where('date', Carbon::today()->toDateString())
    ->selectRaw('status, count(*) as count')
    ->groupBy('status')
    ->pluck('count', 'status')
    ->toArray();

echo "Today's Attendance for Amelia's Team:\n";
print_r($attendanceToday);
$totalPresent = ($attendanceToday['present'] ?? 0) + ($attendanceToday['late'] ?? 0);
$totalTeam = User::where('manager_id', $managerId)->count();
echo "Total Present/Late: {$totalPresent} / {$totalTeam}\n\n";

echo "Generating Daily Performance Report...\n";
$reportService = app(ReportService::class);
try {
    $report = $reportService->generateReport($managerId, 'daily', Carbon::today()->startOfDay(), Carbon::today()->endOfDay());
    echo "Report generated successfully!\n";
    echo "Report ID: {$report->id}\n";
    echo "Manager Score: {$report->manager_score}%\n";
    echo "Metrics Details:\n";
    print_r($report->metrics_json);
    echo "\nAI Insights:\n";
    print_r($report->ai_insights_json);
} catch (\Exception $e) {
    echo "Error generating report: " . $e->getMessage() . "\n";
}
