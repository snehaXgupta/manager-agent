<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login screen.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle authentication request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'ends_with:gmail.com,company.com'],
            'password' => ['required', 'string'],
            'role' => ['required', 'string', 'in:admin,manager,team_lead,employee'],
        ], [
            'email.ends_with' => 'Only authorized email addresses (ending with @gmail.com or @company.com) are allowed to log in.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // Validate role access
        $selectedRole = $request->role;
        
        if ($selectedRole === 'admin' && $user->role !== 'admin') {
            throw ValidationException::withMessages([
                'role' => ['You do not have access permissions for the Admin portal.'],
            ]);
        }

        if ($selectedRole === 'manager' && !in_array($user->role, ['manager', 'team_lead'])) {
            throw ValidationException::withMessages([
                'role' => ['You do not have access permissions for the Manager portal.'],
            ]);
        }

        if ($selectedRole === 'team_lead' && $user->role !== 'team_lead') {
            throw ValidationException::withMessages([
                'role' => ['You do not have access permissions for the Team Lead portal.'],
            ]);
        }

        if ($selectedRole === 'employee' && !in_array($user->role, ['employee', 'team_lead', 'manager'])) {
            throw ValidationException::withMessages([
                'role' => ['You do not have access permissions for the Employee portal.'],
            ]);
        }

        // Log the user in
        Auth::login($user);

        // Store active role in session
        session(['active_role' => $selectedRole]);

        // Redirect based on active role
        if ($selectedRole === 'admin') {
            return redirect()->route('admin.index');
        } elseif ($selectedRole === 'manager' || $selectedRole === 'team_lead') {
            return redirect()->route('dashboard.index');
        } else {
            return redirect()->route('employee.dashboard');
        }
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Switch role for manager user.
     */
    public function switchRole(Request $request)
    {
        $user = Auth::user();

        if (!$user || !in_array($user->role, ['manager', 'team_lead'])) {
            return redirect()->back()->with('error', 'Only managers and team leads can switch roles.');
        }

        $currentActiveRole = session('active_role');
        if (!$currentActiveRole) {
            $currentActiveRole = $user->role;
        }

        $newActiveRole = ($currentActiveRole === 'employee') ? $user->role : 'employee';

        session(['active_role' => $newActiveRole]);

        if (in_array($newActiveRole, ['manager', 'team_lead'])) {
            return redirect()->route('dashboard.index')->with('success', 'Switched to ' . ucfirst($newActiveRole) . ' View');
        } else {
            return redirect()->route('employee.dashboard')->with('success', 'Switched to Employee View');
        }
    }
}
