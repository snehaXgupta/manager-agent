<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PerformanceReport;
use App\Models\User;
use App\Services\PerformanceAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ManagerPerformanceController extends Controller
{
    protected $analyticsService;

    public function __construct(PerformanceAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get performance metrics for a manager's team.
     */
    public function show(Request $request, $id)
    {
        // 1. Verify user exists and is a manager
        $manager = User::where('id', $id)
            ->where('role', 'manager')
            ->first();

        if (!$manager) {
            return response()->json([
                'error' => 'Manager not found or user does not have manager role.'
            ], 404);
        }

        // 2. Determine date range
        $period = $request->query('period', 'weekly');
        $endDate = Carbon::now()->endOfDay();

        if ($period === 'monthly') {
            $startDate = Carbon::now()->subDays(29)->startOfDay();
        } elseif ($period === 'custom') {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);
            $startDate = Carbon::parse($request->query('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->query('end_date'))->endOfDay();
        } else { // default to weekly (last 7 days)
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $period = 'weekly';
        }

        // 3. Compute metrics via deterministic Analytics Service
        $metrics = $this->analyticsService->calculateTeamMetrics($manager->id, $startDate, $endDate);

        // 4. Save basic performance report structure to DB
        $report = PerformanceReport::create([
            'manager_id' => $manager->id,
            'report_type' => $period,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'metrics_json' => $metrics,
            'manager_score' => $metrics['manager_score'],
            'generated_at' => Carbon::now(),
        ]);

        // 5. Return JSON payload
        return response()->json([
            'manager' => [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
            ],
            'period' => $period,
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'report_id' => $report->id,
            'metrics' => $metrics
        ], 200);
    }
}
