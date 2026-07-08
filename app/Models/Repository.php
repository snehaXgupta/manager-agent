<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repository extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'gitlab_project_id',
        'repository_name',
        'repository_url',
        'visibility',
    ];

    /**
     * Relates to the project this repository belongs to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Relates to commits in this repository.
     */
    public function commits()
    {
        return $this->hasMany(Commit::class);
    }

    /**
     * Relates to merge requests in this repository.
     */
    public function mergeRequests()
    {
        return $this->hasMany(MergeRequest::class);
    }
}
