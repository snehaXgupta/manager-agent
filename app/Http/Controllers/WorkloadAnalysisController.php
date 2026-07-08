<?php

namespace App\Http\Controllers;

use App\Services\WorkloadAnalysisService;
use Illuminate\Http\Request;

class WorkloadAnalysisController extends Controller
{
    protected $workloadService;

    public function __construct(WorkloadAnalysisService $workloadService)
    {
        $this->workloadService = $workloadService;
    }

    /**
     * Display workload analysis and optimization recommendations.
     */
    public function index()
    {
        $managerId = auth()->user()->id;
        $workload = $this->workloadService->analyzeWorkload($managerId, 15);

        return view('dashboard.workload.index', compact('workload'));
    }
}
