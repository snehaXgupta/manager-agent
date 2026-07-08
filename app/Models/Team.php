<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'manager_id',
    ];

    /**
     * Relates to the manager who created and supervises this team.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Relates to the employees who are members of this team.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user')
                    ->withTimestamps();
    }

    /**
     * Relates to tasks assigned to this team.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'team_id');
    }

    /**
     * Relates to meetings scheduled for this team.
     */
    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'team_id');
    }
}
