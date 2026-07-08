<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $requiredRole): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $activeRole = session('active_role');

        if (!$activeRole) {
            $activeRole = $user->role;
            session(['active_role' => $activeRole]);
        }

        // Validate if user is allowed to act in this role
        if ($requiredRole === 'admin' && $user->role !== 'admin') {
            abort(403, 'Unauthorized. Admin role required.');
        }

        if ($requiredRole === 'manager') {
            if (!in_array($user->role, ['manager', 'team_lead']) || !in_array($activeRole, ['manager', 'team_lead'])) {
                // Redirect to employee view if they toggled to employee
                if (in_array($user->role, ['manager', 'team_lead']) && $activeRole === 'employee') {
                    return redirect()->route('employee.dashboard');
                }
                abort(403, 'Unauthorized. Manager or Team Lead role required.');
            }
        }

        if ($requiredRole === 'employee') {
            if (!in_array($user->role, ['employee', 'team_lead', 'manager'])) {
                abort(403, 'Unauthorized. Employee, Team Lead, or Manager role required.');
            }
            if ($activeRole !== 'employee') {
                return redirect()->route('dashboard.index');
            }
        }

        return $next($request);
    }
}
