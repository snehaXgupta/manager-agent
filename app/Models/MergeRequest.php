<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MergeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'repository_id',
        'employee_id',
        'gitlab_mr_id',
        'title',
        'description',
        'source_branch',
        'target_branch',
        'status',
    ];

    /**
     * Relates to the project this MR belongs to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Relates to the repository this MR is in.
     */
    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }

    /**
     * Relates to the author employee.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Relates to discussion notes/reviews for this MR.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Relates to approval tracking for this MR.
     */
    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }
}
