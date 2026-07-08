<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'merge_request_id',
        'reviewer_id',
        'comment',
        'status',
    ];

    /**
     * Relates to the merge request.
     */
    public function mergeRequest()
    {
        return $this->belongsTo(MergeRequest::class);
    }

    /**
     * Relates to the reviewer.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
