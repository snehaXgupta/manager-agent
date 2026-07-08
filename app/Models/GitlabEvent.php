<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GitlabEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'project_id',
        'repository_id',
        'payload_json',
        'received_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'received_at' => 'datetime',
    ];

    /**
     * Relates to the associated local project.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Relates to the associated repository.
     */
    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }
}
