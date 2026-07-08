<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display Employee Portal's Attendance calendar and Leave list.
     */
    public function employeeIndex(Request $request)
    {
        $employee = auth()->user();
        
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        // Don't calculate for future days in the current month
        if ($endDate->isFuture()) {
            $endDate = Carbon::now()->endOfDay();
        }

        // Get monthly attendance logs
        $logs = AttendanceLog::where('user_id', $employee->id)
            ->whereBetween('date', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->get()
            ->keyBy(function($log) {
                return Carbon::parse($log->date)->toDateString();
            });

        // Compute metrics
        $metrics = $this->calculateEmployeeAttendanceMetrics($employee, $startDate, $endDate);

        // Fetch leave requests
        $leaves = LeaveRequest::where('user_id', $employee->id)
            ->orderBy('start_date', 'desc')
            ->get();

        return view('employee.attendance.index', compact('employee', 'logs', 'metrics', 'leaves', 'month', 'year', 'startDate', 'endDate'));
    }

    /**
     * Submit a leave request.
     */
    public function storeLeaveRequest(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|string|in:sick,casual,vacation,other',
            'reason' => 'nullable|string|max:1000',
        ]);

        $employee = auth()->user();

        // Check if there is already an active leave request covering this range
        $existing = LeaveRequest::where('user_id', $employee->id)
            ->where('status', '!=', 'rejected')
            ->where(function($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function($sub) use ($request) {
                      $sub->where('start_date', '<=', $request->start_date)
                          ->where('end_date', '>=', $request->end_date);
                  });
            })
            ->exists();

        if ($existing) {
            return redirect()->back()->with('error', 'You already have an active leave request covering this period.');
        }

        LeaveRequest::create([
            'user_id' => $employee->id,
            'start_date' => Carbon::parse($request->start_date),
            'end_date' => Carbon::parse($request->end_date),
            'type' => $request->type,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Leave request submitted successfully.');
    }

    /**
     * Display Manager's Attendance dashboard.
     */
    public function managerIndex(Request $request)
    {
        $manager = auth()->user();
        
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        // Don't calculate metrics for future days
        if ($endDate->isFuture()) {
            $endDate = Carbon::now()->endOfDay();
        }

        $employeesPaginator = User::where('manager_id', $manager->id)
            ->where('role', 'employee')
            ->paginate(15)
            ->withQueryString();

        $employees = $employeesPaginator->items();
        $employeeIds = collect($employees)->pluck('id')->toArray();

        // Calculate workdays (weekdays) in the period
        $workdays = 0;
        $tempDate = clone $startDate;
        while ($tempDate <= $endDate) {
            if (!$tempDate->isWeekend()) {
                $workdays++;
            }
            $tempDate->addDay();
        }

        // Bulk fetch approved leaves within the period for paginated employees only
        $approvedLeaves = LeaveRequest::whereIn('user_id', $employeeIds)
            ->where('status', 'approved')
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function($sub) use ($startDate, $endDate) {
                      $sub->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                  });
            })
            ->get()
            ->groupBy('user_id');

        // Leave Utilization based on the calendar year of $startDate (quota = 20 weekdays per year) for paginated employees
        $yearStart = Carbon::createFromDate($startDate->year, 1, 1)->startOfYear();
        $yearEnd = Carbon::createFromDate($startDate->year, 12, 31)->endOfYear();
        
        $yearApprovedLeaves = LeaveRequest::whereIn('user_id', $employeeIds)
            ->where('status', 'approved')
            ->where(function($q) use ($yearStart, $yearEnd) {
                $q->whereBetween('start_date', [$yearStart, $yearEnd])
                  ->orWhereBetween('end_date', [$yearStart, $yearEnd])
                  ->orWhere(function($sub) use ($yearStart, $yearEnd) {
                      $sub->where('start_date', '<=', $yearStart)
                          ->where('end_date', '>=', $yearEnd);
                  });
            })
            ->get()
            ->groupBy('user_id');

        // Bulk fetch attendance logs within the period for paginated employees
        $logs = AttendanceLog::whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->get()
            ->groupBy('user_id');

        $employeesMetrics = [];

        foreach ($employees as $employee) {
            $employeeId = $employee->id;

            // 1. Calculate leave weekdays in period
            $userLeaves = $approvedLeaves->get($employeeId) ?? collect();
            $leaveWeekdays = 0;
            foreach ($userLeaves as $leave) {
                $leaveStart = Carbon::parse($leave->start_date);
                $leaveEnd = Carbon::parse($leave->end_date);
                $lStart = $leaveStart->gt($startDate) ? $leaveStart : $startDate;
                $lEnd = $leaveEnd->lt($endDate) ? $leaveEnd : $endDate;
                $lTemp = clone $lStart;
                while ($lTemp <= $lEnd) {
                    if (!$lTemp->isWeekend()) {
                        $leaveWeekdays++;
                    }
                    $lTemp->addDay();
                }
            }
            $expectedDays = max(0, $workdays - $leaveWeekdays);

            // 2. Fetch logs
            $userLogs = $logs->get($employeeId) ?? collect();
            $presentCount = $userLogs->whereIn('status', ['present', 'late'])->count();
            $lateCount = $userLogs->where('status', 'late')->count();
            $earlyExits = $userLogs->where('is_early_exit', true)->count();
            $absentCount = max(0, $expectedDays - $presentCount);
            
            $attendancePercentage = $expectedDays > 0 ? ($presentCount / $expectedDays) * 100 : 100.0;

            // 3. Calculate year leave weekdays
            $userYearLeaves = $yearApprovedLeaves->get($employeeId) ?? collect();
            $totalYearLeaveWeekdays = 0;
            foreach ($userYearLeaves as $leave) {
                $leaveStart = Carbon::parse($leave->start_date);
                $leaveEnd = Carbon::parse($leave->end_date);
                $lStart = $leaveStart->gt($yearStart) ? $leaveStart : $yearStart;
                $lEnd = $leaveEnd->lt($yearEnd) ? $yearEnd : $yearEnd;
                $lTemp = clone $lStart;
                while ($lTemp <= $lEnd) {
                    if (!$lTemp->isWeekend()) {
                        $totalYearLeaveWeekdays++;
                    }
                    $lTemp->addDay();
                }
            }
            $leaveUtilization = min(100.0, ($totalYearLeaveWeekdays / 20) * 100);

            $score = 100 - ($lateCount * 5) - ($absentCount * 15) - ($earlyExits * 5);
            $score = max(0, min(100, $score));

            $empMetrics = [
                'expected_days' => $expectedDays,
                'present_days' => $presentCount,
                'late_days' => $lateCount,
                'early_exits' => $earlyExits,
                'absent_days' => $absentCount,
                'attendance_percentage' => round($attendancePercentage, 2),
                'leave_utilization' => round($leaveUtilization, 2),
                'attendance_score' => $score,
                'total_leave_days_year' => $totalYearLeaveWeekdays,
            ];

            $employeesMetrics[] = [
                'employee' => $employee,
                'metrics' => $empMetrics,
            ];
        }

        // --- BULK DATABASE AGGREGATIONS FOR TEAM STATS ---
        $teamUserSubquery = User::select('id')->where('manager_id', $manager->id)->where('role', 'employee');
        $totalTeam = User::where('manager_id', $manager->id)->where('role', 'employee')->count();

        // 1. Logs statistics for the whole team in the period
        $teamLogsStats = AttendanceLog::whereIn('user_id', $teamUserSubquery)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw("
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN is_early_exit THEN 1 ELSE 0 END) as early_exit_count
            ")
            ->first();

        $teamLateCount = (int) ($teamLogsStats->late_count ?? 0);
        $teamPresentCount = (int) ($teamLogsStats->present_count ?? 0);
        $teamEarlyExits = (int) ($teamLogsStats->early_exit_count ?? 0);

        // 2. Leaves in the period for the whole team
        $teamLeaves = LeaveRequest::whereIn('user_id', $teamUserSubquery)
            ->where('status', 'approved')
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function($sub) use ($startDate, $endDate) {
                      $sub->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                  });
            })
            ->get();

        $totalTeamLeaveWeekdays = 0;
        foreach ($teamLeaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);
            $lStart = $leaveStart->gt($startDate) ? $leaveStart : $startDate;
            $lEnd = $leaveEnd->lt($endDate) ? $leaveEnd : $endDate;
            $lTemp = clone $lStart;
            while ($lTemp <= $lEnd) {
                if (!$lTemp->isWeekend()) {
                    $totalTeamLeaveWeekdays++;
                }
                $lTemp->addDay();
            }
        }

        $totalExpectedDays = max(0, ($workdays * $totalTeam) - $totalTeamLeaveWeekdays);
        $avgAttendance = $totalExpectedDays > 0 ? round(($teamPresentCount / $totalExpectedDays) * 100, 2) : 100.0;
        
        $teamAbsentCount = max(0, $totalExpectedDays - $teamPresentCount);
        $totalScoreSum = ($totalTeam * 100) - ($teamLateCount * 5) - ($teamAbsentCount * 15) - ($teamEarlyExits * 5);
        $avgScore = $totalTeam > 0 ? round(max(0, min(100, $totalScoreSum / $totalTeam)), 2) : 100.0;

        // 3. Yearly leaves for the whole team
        $teamYearLeaves = LeaveRequest::whereIn('user_id', $teamUserSubquery)
            ->where('status', 'approved')
            ->where(function($q) use ($yearStart, $yearEnd) {
                $q->whereBetween('start_date', [$yearStart, $yearEnd])
                  ->orWhereBetween('end_date', [$yearStart, $yearEnd])
                  ->orWhere(function($sub) use ($yearStart, $yearEnd) {
                      $sub->where('start_date', '<=', $yearStart)
                          ->where('end_date', '>=', $yearEnd);
                  });
            })
            ->get();

        $totalYearTeamLeaveWeekdays = 0;
        foreach ($teamYearLeaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);
            $lStart = $leaveStart->gt($yearStart) ? $leaveStart : $yearStart;
            $lEnd = $leaveEnd->lt($yearEnd) ? $yearEnd : $yearEnd;
            $lTemp = clone $lStart;
            while ($lTemp <= $lEnd) {
                if (!$lTemp->isWeekend()) {
                    $totalYearTeamLeaveWeekdays++;
                }
                $lTemp->addDay();
            }
        }

        $avgLeaveDays = $totalTeam > 0 ? round($totalYearTeamLeaveWeekdays / $totalTeam, 1) : 0.0;
        $avgLeaveUtilization = min(100.0, ($avgLeaveDays / 20) * 100);

        // Fetch pending leave requests for direct reports (using team subquery)
        $pendingLeaves = LeaveRequest::whereIn('user_id', $teamUserSubquery)
            ->where('status', 'pending')
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        // Calculate absenteeism trends in bulk
        $absenteeismTrends = $this->calculateAbsenteeismTrends($manager->id);

        return view('dashboard.attendance.index', compact(
            'month', 'year', 'startDate', 'endDate',
            'employeesMetrics', 'pendingLeaves',
            'avgScore', 'avgAttendance', 'avgLeaveUtilization', 'avgLeaveDays',
            'absenteeismTrends', 'employeesPaginator'
        ));
    }

    /**
     * Approve a leave request.
     */
    public function approveLeave($id)
    {
        $manager = auth()->user();
        $leave = LeaveRequest::with('user')->findOrFail($id);

        if ($leave->user->manager_id !== $manager->id) {
            abort(403, 'Unauthorized.');
        }

        $leave->update([
            'status' => 'approved',
            'approved_by' => $manager->id,
        ]);

        return redirect()->back()->with('success', 'Leave request approved successfully.');
    }

    /**
     * Reject a leave request.
     */
    public function rejectLeave($id)
    {
        $manager = auth()->user();
        $leave = LeaveRequest::with('user')->findOrFail($id);

        if ($leave->user->manager_id !== $manager->id) {
            abort(403, 'Unauthorized.');
        }

        $leave->update([
            'status' => 'rejected',
            'approved_by' => $manager->id,
        ]);

        return redirect()->back()->with('success', 'Leave request rejected.');
    }

    /**
     * Calculate all metrics for a single employee over a date range.
     */
    private function calculateEmployeeAttendanceMetrics(User $employee, Carbon $startDate, Carbon $endDate)
    {
        // 1. Calculate actual workdays (weekdays) in period
        $workdays = 0;
        $tempDate = clone $startDate;
        while ($tempDate <= $endDate) {
            if (!$tempDate->isWeekend()) {
                $workdays++;
            }
            $tempDate->addDay();
        }

        // 2. Fetch approved leaves within the period and calculate their weekdays
        $approvedLeaves = LeaveRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function($sub) use ($startDate, $endDate) {
                      $sub->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                  });
            })
            ->get();

        $leaveWeekdays = 0;
        foreach ($approvedLeaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);
            $lStart = $leaveStart->gt($startDate) ? $leaveStart : $startDate;
            $lEnd = $leaveEnd->lt($endDate) ? $leaveEnd : $endDate;
            $lTemp = clone $lStart;
            while ($lTemp <= $lEnd) {
                if (!$lTemp->isWeekend()) {
                    $leaveWeekdays++;
                }
                $lTemp->addDay();
            }
        }

        // Expected attendance days is workdays minus approved leave weekdays
        $expectedDays = max(0, $workdays - $leaveWeekdays);

        // 3. Fetch attendance logs
        $logs = AttendanceLog::where('user_id', $employee->id)
            ->whereBetween('date', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->get();

        $presentCount = $logs->whereIn('status', ['present', 'late'])->count();
        $lateCount = $logs->where('status', 'late')->count();
        $earlyExits = $logs->where('is_early_exit', true)->count();

        // Absent days count
        $absentCount = max(0, $expectedDays - $presentCount);

        // Attendance Percentage
        $attendancePercentage = $expectedDays > 0 ? ($presentCount / $expectedDays) * 100 : 100.0;

        // Leave Utilization based on the calendar year of $startDate (quota = 20 weekdays per year)
        $yearStart = Carbon::createFromDate($startDate->year, 1, 1)->startOfYear();
        $yearEnd = Carbon::createFromDate($startDate->year, 12, 31)->endOfYear();
        $yearApprovedLeaves = LeaveRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->where(function($q) use ($yearStart, $yearEnd) {
                $q->whereBetween('start_date', [$yearStart, $yearEnd])
                  ->orWhereBetween('end_date', [$yearStart, $yearEnd])
                  ->orWhere(function($sub) use ($yearStart, $yearEnd) {
                      $sub->where('start_date', '<=', $yearStart)
                          ->where('end_date', '>=', $yearEnd);
                  });
            })
            ->get();

        $totalYearLeaveWeekdays = 0;
        foreach ($yearApprovedLeaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);
            $lStart = $leaveStart->gt($yearStart) ? $leaveStart : $yearStart;
            $lEnd = $leaveEnd->lt($yearEnd) ? $leaveEnd : $yearEnd;
            $lTemp = clone $lStart;
            while ($lTemp <= $lEnd) {
                if (!$lTemp->isWeekend()) {
                    $totalYearLeaveWeekdays++;
                }
                $lTemp->addDay();
            }
        }
        $leaveUtilization = min(100.0, ($totalYearLeaveWeekdays / 20) * 100);

        // Attendance Score Formula: 100 - (late * 5) - (absent * 15) - (early_exits * 5)
        $score = 100 - ($lateCount * 5) - ($absentCount * 15) - ($earlyExits * 5);
        $score = max(0, min(100, $score));

        return [
            'expected_days' => $expectedDays,
            'present_days' => $presentCount,
            'late_days' => $lateCount,
            'early_exits' => $earlyExits,
            'absent_days' => $absentCount,
            'attendance_percentage' => round($attendancePercentage, 2),
            'leave_utilization' => round($leaveUtilization, 2),
            'attendance_score' => $score,
            'total_leave_days_year' => $totalYearLeaveWeekdays,
        ];
    }

    /**
     * Compute team absences count over the last 4 weeks in bulk.
     */
    private function calculateAbsenteeismTrends(int $managerId)
    {
        $trends = [];
        $totalTeam = User::where('manager_id', $managerId)->where('role', 'employee')->count();
        $teamUserSubquery = User::select('id')->where('manager_id', $managerId)->where('role', 'employee');
        
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();

            // Calculate workdays in this week
            $weekWorkdays = 0;
            $temp = clone $weekStart;
            while ($temp <= $weekEnd) {
                if (!$temp->isWeekend()) {
                    $weekWorkdays++;
                }
                $temp->addDay();
            }

            // Weekly leaves count for the entire team in bulk
            $weekLeaves = LeaveRequest::whereIn('user_id', $teamUserSubquery)
                ->where('status', 'approved')
                ->where(function($q) use ($weekStart, $weekEnd) {
                    $q->whereBetween('start_date', [$weekStart, $weekEnd])
                      ->orWhereBetween('end_date', [$weekStart, $weekEnd])
                      ->orWhere(function($sub) use ($weekStart, $weekEnd) {
                          $sub->where('start_date', '<=', $weekStart)
                              ->where('end_date', '>=', $weekEnd);
                      });
                })
                ->get();

            $weekLeaveDaysCount = 0;
            foreach ($weekLeaves as $leave) {
                $leaveStart = Carbon::parse($leave->start_date);
                $leaveEnd = Carbon::parse($leave->end_date);
                $lStart = $leaveStart->gt($weekStart) ? $leaveStart : $weekStart;
                $lEnd = $leaveEnd->lt($weekEnd) ? $leaveEnd : $weekEnd;
                $lTemp = clone $lStart;
                while ($lTemp <= $lEnd) {
                    if (!$lTemp->isWeekend()) {
                        $weekLeaveDaysCount++;
                    }
                    $lTemp->addDay();
                }
            }

            $weekExpectedDays = max(0, ($weekWorkdays * $totalTeam) - $weekLeaveDaysCount);
            $weekPresentDays = AttendanceLog::whereIn('user_id', $teamUserSubquery)
                ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->whereIn('status', ['present', 'late'])
                ->count();

            $weekAbsences = max(0, $weekExpectedDays - $weekPresentDays);

            $trends[] = [
                'label' => $weekStart->format('d M'),
                'absences' => $weekAbsences,
            ];
        }

        return $trends;
    }
}
