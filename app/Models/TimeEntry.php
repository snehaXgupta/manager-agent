<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'started_at',
        'stopped_at',
        'duration_seconds',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    /**
     * Relates to the task this time entry is for.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Relates to the user who tracked this time entry.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
