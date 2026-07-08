<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RiskDetectionService;
use App\Services\TeamHealthService;
use App\Services\WorkloadAnalysisService;
use App\Services\ReportService;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;

class PredictiveAnalyticsController extends Controller
{
    protected $riskService;
    protected $healthService;
    protected $workloadService;
    protected $reportService;

    public function __construct(
        RiskDetectionService $riskService,
        TeamHealthService $healthService,
        WorkloadAnalysisService $workloadService,
        ReportService $reportService
    ) {
        $this->riskService = $riskService;
        $this->healthService = $healthService;
        $this->workloadService = $workloadService;
        $this->reportService = $reportService;
    }

    /**
     * Verify user is a manager.
     */
    protected function verifyManager($id)
    {
        $manager = User::where('id', $id)->where('role', 'manager')->first();
        if (!$manager) {
            return response()->json(['error' => 'Manager not found or user does not have manager role.'], 404);
        }
        return $manager;
    }

    /**
     * GET /api/managers/{id}/predictive-health
     */
    public function getHealth($id)
    {
        $manager = $this->verifyManager($id);
        if ($manager instanceof \Illuminate\Http\JsonResponse) {
            return $manager;
        }

        $health = $this->healthService->calculateTeamHealth($id);
        return response()->json($health, 200);
    }

    /**
     * GET /api/managers/{id}/predictive-risks
     */
    public function getRisks($id)
    {
        $manager = $this->verifyManager($id);
        if ($manager instanceof \Illuminate\Http\JsonResponse) {
            return $manager;
        }

        // Run detection scan
        $this->riskService->detectTeamRisks($id);
        $risks = $this->riskService->getActiveRisksForManager($id);

        return response()->json($risks, 200);
    }

    /**
     * GET /api/managers/{id}/workload-distribution
     */
    public function getWorkload($id)
    {
        $manager = $this->verifyManager($id);
        if ($manager instanceof \Illuminate\Http\JsonResponse) {
            return $manager;
        }

        $workload = $this->workloadService->analyzeWorkload($id, 15);
        return response()->json($workload, 200);
    }

    /**
     * POST /api/managers/{id}/generate-predictive-report
     */
    public function generateReport(Request $request, $id)
    {
        $manager = $this->verifyManager($id);
        if ($manager instanceof \Illuminate\Http\JsonResponse) {
            return $manager;
        }

        $request->validate([
            'report_type' => 'required|string|in:daily,weekly,monthly',
        ]);

        $reportType = $request->input('report_type');

        try {
            // Generates report containing both standard & predictive structures
            $report = $this->reportService->generateReport($id, $reportType);
            $comparison = $this->reportService->compareWithPrevious($report);

            return response()->json([
                'message' => 'Predictive performance report generated successfully.',
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
