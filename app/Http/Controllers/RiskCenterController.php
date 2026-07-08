<?php

namespace App\Http\Controllers;

use App\Services\RiskDetectionService;
use App\Services\RiskReportService;
use App\Models\RiskAlert;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RiskCenterController extends Controller
{
    protected $riskService;
    protected $reportService;

    public function __construct(RiskDetectionService $riskService, RiskReportService $reportService)
    {
        $this->riskService = $riskService;
        $this->reportService = $reportService;
    }

    /**
     * Display a listing of active risks.
     */
    public function index()
    {
        return view('dashboard.risks.index');
    }

    /**
     * Get JSON data for the Risk Center dashboard.
     */
    public function getRisksData(Request $request)
    {
        $managerId = auth()->user()->id;

        // Proactively scan for new risks first (highly optimized and throttled)
        $this->riskService->detectTeamRisks($managerId);

        // Fetch statistics & team health
        $health = $this->reportService->getTeamHealthSummary($managerId);
        $stats = $this->reportService->getRiskStats($managerId);
        $ai = $this->reportService->getAiInsightsAndRecommendations($managerId);

        // Fetch trend data based on period query
        $period = $request->query('trend_period', 'daily');
        $trends = $this->reportService->getRiskTrendData($managerId, $period);

        // Server-side filtering, eager loaded for performance
        $employeesSubquery = User::select('id')->where('manager_id', $managerId);

        $query = RiskAlert::whereIn('employee_id', $employeesSubquery)
            ->with('employee');

        // Filter: Status / Tab
        $statusTab = $request->query('tab', 'all');
        if ($statusTab === 'resolved') {
            $query->where('is_resolved', true);
        } elseif ($statusTab === 'high') {
            $query->where('is_resolved', false)->where('risk_level', 'high');
        } elseif ($statusTab === 'medium') {
            $query->where('is_resolved', false)->where('risk_level', 'medium');
        } elseif ($statusTab === 'low') {
            $query->where('is_resolved', false)->where('risk_level', 'low');
        } else {
            // 'all' tab shows active risks
            $query->where('is_resolved', false);
        }

        // Filter: Employee text search
        if ($request->filled('employee')) {
            $search = $request->query('employee');
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Filter: Risk Type
        if ($request->filled('risk_type') && $request->query('risk_type') !== 'all') {
            $query->where('risk_type', $request->query('risk_type'));
        }

        // Filter: Risk Severity (overrides tab filter if specified)
        if ($request->filled('risk_severity') && $request->query('risk_severity') !== 'all') {
            $query->where('risk_level', $request->query('risk_severity'));
        }

        // Filter: Resolved status override
        if ($request->filled('status') && $request->query('status') !== 'all') {
            $resolvedStatus = $request->query('status') === 'resolved';
            $query->where('is_resolved', $resolvedStatus);
        }

        // Filter: Date Range
        if ($request->filled('start_date')) {
            $query->where('detected_at', '>=', Carbon::parse($request->query('start_date'))->startOfDay());
        }
        if ($request->filled('end_date')) {
            $query->where('detected_at', '<=', Carbon::parse($request->query('end_date'))->endOfDay());
        }

        // Order and Paginate (limit N+1)
        $risks = $query->orderByRaw("CASE WHEN risk_level = 'high' THEN 1 WHEN risk_level = 'medium' THEN 2 ELSE 3 END")
            ->orderBy('detected_at', 'desc')
            ->paginate(15);

        // Map and format detected_at for frontend
        $risks->getCollection()->transform(function ($risk) {
            $risk->formatted_date = $risk->detected_at->format('M d, Y h:i A');
            $risk->time_ago = $risk->detected_at->diffForHumans();
            return $risk;
        });

        return response()->json([
            'health' => $health,
            'stats' => $stats,
            'ai' => $ai,
            'trends' => $trends,
            'risks' => $risks
        ]);
    }

    /**
     * Mark a risk alert as resolved.
     */
    public function resolve(Request $request, $id)
    {
        $risk = RiskAlert::findOrFail($id);
        
        // Ensure the manager owns the employee
        if ($risk->employee->manager_id !== auth()->user()->id) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $risk->update(['is_resolved' => true]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Risk alert resolved successfully.']);
        }

        return redirect()->back()->with('success', 'Risk alert has been resolved successfully.');
    }

    /**
     * Save manager notes.
     */
    public function saveNotes(Request $request, $id)
    {
        $request->validate([
            'manager_notes' => 'required|string'
        ]);

        $risk = RiskAlert::findOrFail($id);

        if ($risk->employee->manager_id !== auth()->user()->id) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $risk->update(['manager_notes' => $request->manager_notes]);

        return response()->json(['success' => true, 'message' => 'Notes saved successfully.']);
    }

    /**
     * Assign follow-up action.
     */
    public function assignFollowUp(Request $request, $id)
    {
        $request->validate([
            'follow_up_action' => 'required|string|max:255'
        ]);

        $risk = RiskAlert::findOrFail($id);

        if ($risk->employee->manager_id !== auth()->user()->id) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $risk->update(['follow_up_action' => $request->follow_up_action]);

        return response()->json(['success' => true, 'message' => 'Follow-up action assigned successfully.']);
    }
}
