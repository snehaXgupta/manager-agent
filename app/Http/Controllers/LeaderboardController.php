<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use App\Models\Department;
use App\Models\Team;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    protected $leaderboardService;

    public function __construct(LeaderboardService $leaderboardService)
    {
        $this->leaderboardService = $leaderboardService;
    }

    public function index(Request $request)
    {
        $manager = auth()->user();
        
        $tab = $request->query('tab', 'individual');
        if (!in_array($tab, ['individual', 'team'])) {
            $tab = 'individual';
        }

        $period = $request->query('period', 'weekly');
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'])) {
            $period = 'weekly';
        }

        $startDateStr = $request->query('start_date');
        $endDateStr = $request->query('end_date');

        // Resolve dates
        $dates = $this->leaderboardService->getPeriodDates($period, $startDateStr, $endDateStr);

        // Fetch both leaderboard datasets for instant client-side switching
        $individualFilters = [
            'scope' => $request->query('scope', 'team'), // 'team' (my direct reports) or 'all'
            'department_id' => $request->query('department_id'),
            'team_id' => $request->query('team_id'),
        ];
        
        $individualRankings = $this->leaderboardService->getIndividualLeaderboard(
            $manager->id,
            $dates['start'],
            $dates['end'],
            $individualFilters
        );

        $teamRankings = $this->leaderboardService->getTeamLeaderboard(
            $manager->id,
            $dates['start'],
            $dates['end']
        );

        // Fetch lookup items for dropdown menus
        $departments = Department::orderBy('name', 'asc')->get();
        $teams = Team::where('manager_id', $manager->id)->orderBy('name', 'asc')->get();

        return view('dashboard.leaderboard.index', compact(
            'tab',
            'period',
            'dates',
            'individualRankings',
            'teamRankings',
            'departments',
            'teams',
            'startDateStr',
            'endDateStr'
        ));
    }
}
