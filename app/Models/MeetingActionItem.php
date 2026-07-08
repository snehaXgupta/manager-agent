<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingActionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'assigned_to',
        'action_item',
        'due_date',
        'priority',
        'status',
        'fireflies_action_item_id',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    /**
     * Relates back to the meeting.
     */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    /**
     * Relates to the user assigned to this action item.
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
