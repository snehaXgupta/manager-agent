<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'merge_request_id',
        'approved_by',
        'approval_date',
    ];

    protected $casts = [
        'approval_date' => 'datetime',
    ];

    /**
     * Relates to the merge request.
     */
    public function mergeRequest()
    {
        return $this->belongsTo(MergeRequest::class);
    }

    /**
     * Relates to the user who approved it.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
