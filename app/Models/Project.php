<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'manager_id',
        'status',
        'category',
        'is_archived',
        'deadline',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'deadline' => 'date',
    ];

    /**
     * Relates to the manager supervising this project.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Relates to the employees who are members of this project.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members')
                    ->withPivot('gitlab_member_id')
                    ->withTimestamps();
    }

    /**
     * Relates to the GitLab repository associated with this project.
     */
    public function repository()
    {
        return $this->hasOne(Repository::class);
    }

    /**
     * Relates to the tasks belonging to this project.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
