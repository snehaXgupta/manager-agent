<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Display the Admin Dashboard.
     */
    public function index()
    {
        $users = User::with(['manager', 'department', 'designation'])->orderBy('created_at', 'desc')->get();
        $managers = User::where('role', 'manager')->get();
        $departments = Department::orderBy('name', 'asc')->get();
        $designations = Designation::orderBy('name', 'asc')->get();
        $skills = Skill::orderBy('name', 'asc')->get();

        return view('admin.dashboard', compact('users', 'managers', 'departments', 'designations', 'skills'));
    }

    /**
     * Register a new user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', 'ends_with:gmail.com,company.com'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:employee,manager,team_lead'],
            'manager_id' => ['nullable', 'required_if:role,employee,team_lead', 'exists:users,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
        ], [
            'email.ends_with' => 'Only emails ending with @gmail.com or @company.com can be registered.',
            'manager_id.required_if' => 'Please select a supervisor manager for this role.',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'manager_id' => in_array($request->role, ['employee', 'team_lead']) ? $request->manager_id : null,
            'department_id' => $request->department_id ?: null,
            'designation_id' => $request->designation_id ?: null,
        ]);

        return redirect()->back()->with('success', 'User ' . $request->name . ' successfully created with role: ' . $request->role);
    }

    // Departments CRUD
    public function storeDepartment(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'description' => 'nullable|string'
        ]);

        Department::create($request->only('name', 'description'));

        return redirect()->back()->with('success', 'Department created successfully.');
    }

    public function updateDepartment(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $id,
            'description' => 'nullable|string'
        ]);

        $dept = Department::findOrFail($id);
        $dept->update($request->only('name', 'description'));

        return redirect()->back()->with('success', 'Department updated successfully.');
    }

    public function destroyDepartment($id)
    {
        $dept = Department::findOrFail($id);
        $dept->delete();

        return redirect()->back()->with('success', 'Department deleted successfully.');
    }

    // Designations CRUD
    public function storeDesignation(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:designations,name',
            'description' => 'nullable|string'
        ]);

        Designation::create($request->only('name', 'description'));

        return redirect()->back()->with('success', 'Designation created successfully.');
    }

    public function updateDesignation(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:designations,name,' . $id,
            'description' => 'nullable|string'
        ]);

        $desig = Designation::findOrFail($id);
        $desig->update($request->only('name', 'description'));

        return redirect()->back()->with('success', 'Designation updated successfully.');
    }

    public function destroyDesignation($id)
    {
        $desig = Designation::findOrFail($id);
        $desig->delete();

        return redirect()->back()->with('success', 'Designation deleted successfully.');
    }

    // Skills CRUD
    public function storeSkill(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:skills,name'
        ]);

        Skill::create($request->only('name'));

        return redirect()->back()->with('success', 'Skill created successfully.');
    }

    public function destroySkill($id)
    {
        $skill = Skill::findOrFail($id);
        $skill->delete();

        return redirect()->back()->with('success', 'Skill deleted successfully.');
    }
}

