<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    protected $leaderboardService;

    public function __construct(LeaderboardService $leaderboardService)
    {
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * GET /api/leaderboard
     * Query managers leaderboard (period = weekly|monthly).
     */
    public function index(Request $request)
    {
        $period = $request->query('period', 'weekly');

        if (!in_array($period, ['weekly', 'monthly'])) {
            return response()->json(['error' => 'Invalid period. Supported periods are: weekly, monthly.'], 400);
        }

        $leaderboard = $this->leaderboardService->getLeaderboard($period);

        return response()->json($leaderboard, 200);
    }
}
