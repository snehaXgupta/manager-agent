<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commit extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'repository_id',
        'employee_id',
        'commit_sha',
        'branch',
        'message',
        'files_changed',
        'additions',
        'deletions',
        'committed_at',
    ];

    protected $casts = [
        'committed_at' => 'datetime',
    ];

    /**
     * Relates to the associated project.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Relates to the repository.
     */
    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }

    /**
     * Relates to the employee who made the commit.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
