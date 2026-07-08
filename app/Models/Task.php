<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'deadline',
        'assigned_to',
        'team_id',
        'project_id',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    /**
     * Relates to the user this task is assigned to.
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Relates to the team this task is assigned to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Relates to the project this task belongs to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Relates to time entries tracked against this task.
     */
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Get total logged hours for this task.
     */
    public function getTotalLoggedHoursAttribute()
    {
        $seconds = $this->timeEntries()->sum('duration_seconds');
        
        // Also check if there is an active running timer on this task right now to add live seconds
        $activeTimer = $this->timeEntries()->whereNull('stopped_at')->first();
        if ($activeTimer) {
            $seconds += abs(now()->diffInSeconds(\Illuminate\Support\Carbon::parse($activeTimer->started_at)));
        }

        return round($seconds / 3600, 2);
    }
}
