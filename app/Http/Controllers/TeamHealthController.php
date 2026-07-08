<?php

namespace App\Http\Controllers;

use App\Services\TeamHealthService;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;

class TeamHealthController extends Controller
{
    protected $healthService;
    protected $leaderboardService;

    public function __construct(TeamHealthService $healthService, LeaderboardService $leaderboardService)
    {
        $this->healthService = $healthService;
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Display overall team health metrics.
     */
    public function index(Request $request)
    {
        $managerId = auth()->user()->id;

        $period = $request->query('period', 'weekly');
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'])) {
            $period = 'weekly';
        }

        $startDateStr = $request->query('start_date');
        $endDateStr = $request->query('end_date');

        // Resolve dates using LeaderboardService
        $dates = $this->leaderboardService->getPeriodDates($period, $startDateStr, $endDateStr);

        $health = $this->healthService->calculateTeamHealth($managerId, $dates['start'], $dates['end']);

        $rankings = $this->leaderboardService->getIndividualLeaderboard(
            $managerId,
            $dates['start'],
            $dates['end'],
            ['scope' => 'team']
        );

        return view('dashboard.health.index', compact(
            'health', 
            'rankings', 
            'period', 
            'dates', 
            'startDateStr', 
            'endDateStr'
        ));
    }
}
