<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'manager_id',
        'department_id',
        'designation_id',
        'github_username',
        'gitlab_username',
        'gitlab_user_id',
        'gitlab_email',
        'bitbucket_username',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relates to tasks assigned to this user.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Relates to time entries created by this user.
     */
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Relates to attendance logs for this user.
     */
    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * Relates to leave requests for this user.
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Relates to the manager of this user.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Relates to the employees reporting to this user (if they are a manager).
     */
    public function team()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /**
     * Relates to the teams this user belongs to.
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_user')
                    ->withTimestamps();
    }

    /**
     * Relates to the developer activities for this user.
     */
    public function activities()
    {
        return $this->hasMany(DeveloperActivity::class);
    }

    /**
     * Relates to the active ongoing time entry for this user.
     */
    public function activeTimeEntry()
    {
        return $this->hasOne(TimeEntry::class)->whereNull('stopped_at');
    }

    /**
     * Relates to the latest time entry for this user.
     */
    public function latestTimeEntry()
    {
        return $this->hasOne(TimeEntry::class)->latestOfMany('updated_at');
    }

    /**
     * Relates to projects this user belongs to.
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_members')
                    ->withPivot('gitlab_member_id')
                    ->withTimestamps();
    }

    /**
     * Relates to projects managed by this user.
     */
    public function managedProjects()
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    /**
     * Relates to the department of this user.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relates to the designation of this user.
     */
    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    /**
     * Relates to the skills this user possesses.
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'employee_skill')
                    ->withPivot('proficiency')
                    ->withTimestamps();
    }

    /**
     * Relates to risk alerts for this employee.
     */
    public function riskAlerts()
    {
        return $this->hasMany(RiskAlert::class, 'employee_id');
    }
}
