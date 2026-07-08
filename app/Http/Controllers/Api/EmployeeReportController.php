<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PerformanceReport;
use App\Services\PerformanceAnalyticsService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Exception;

class EmployeeReportController extends Controller
{
    protected $analyticsService;
    protected $reportService;

    public function __construct(PerformanceAnalyticsService $analyticsService, ReportService $reportService)
    {
        $this->analyticsService = $analyticsService;
        $this->reportService = $reportService;
    }

    /**
     * GET /api/employees/{id}/performance
     * Fetch dynamic performance metrics for a single employee.
     */
    public function showPerformance(Request $request, $id)
    {
        $employee = User::find($id);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found.'], 404);
        }

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
        } else { // default to weekly
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $period = 'weekly';
        }

        $metrics = $this->analyticsService->calculateUserMetrics($employee->id, $startDate, $endDate);

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => $employee->role,
            ],
            'period' => $period,
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'metrics' => $metrics
        ], 200);
    }

    /**
     * GET /api/employees/{id}/reports
     * Fetch historical reports for a single employee.
     */
    public function index($id)
    {
        $employee = User::find($id);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found.'], 404);
        }

        $reports = $this->reportService->getHistoricalReports($id);

        return response()->json([
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'reports' => $reports
        ], 200);
    }

    /**
     * GET /api/employees/{id}/reports/{reportId}
     * Show detailed employee report and comparisons.
     */
    public function show($id, $reportId)
    {
        $employee = User::find($id);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found.'], 404);
        }

        $report = $this->reportService->getReportById($reportId);
        if (!$report || $report->manager_id != $id) {
            return response()->json(['error' => 'Report not found.'], 404);
        }

        $comparison = $this->reportService->compareWithPrevious($report);

        return response()->json([
            'report' => $report,
            'comparison' => $comparison
        ], 200);
    }

    /**
     * POST /api/employees/{id}/generate-report
     * Dynamically compile, execute AI analysis, and save report.
     */
    public function store(Request $request, $id)
    {
        $employee = User::find($id);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found.'], 404);
        }

        $request->validate([
            'report_type' => 'required|string|in:daily,weekly,monthly',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $reportType = $request->input('report_type');
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;

        try {
            $report = $this->reportService->generateReport($id, $reportType, $startDate, $endDate);
            $comparison = $this->reportService->compareWithPrevious($report);

            return response()->json([
                'message' => 'Employee performance report generated and analyzed successfully.',
                'report' => $report,
                'comparison' => $comparison
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }
}
