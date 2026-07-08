<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Exception;

class ManagerReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * GET /api/managers/{id}/reports
     * Fetch historical reports for a manager.
     */
    public function index($id)
    {
        $manager = User::where('id', $id)->where('role', 'manager')->first();
        if (!$manager) {
            return response()->json(['error' => 'Manager not found.'], 404);
        }

        $reports = $this->reportService->getHistoricalReports($id);

        return response()->json([
            'manager_id' => $manager->id,
            'manager_name' => $manager->name,
            'reports' => $reports
        ], 200);
    }

    /**
     * GET /api/managers/{id}/reports/{reportId}
     * Show detailed report and comparisons.
     */
    public function show($id, $reportId)
    {
        $manager = User::where('id', $id)->where('role', 'manager')->first();
        if (!$manager) {
            return response()->json(['error' => 'Manager not found.'], 404);
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
     * POST /api/managers/{id}/generate-report
     * Dynamically compile, execute AI analysis, and save report.
     */
    public function store(Request $request, $id)
    {
        $manager = User::where('id', $id)->where('role', 'manager')->first();
        if (!$manager) {
            return response()->json(['error' => 'Manager not found.'], 404);
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
                'message' => 'Performance report generated and analyzed successfully.',
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
