<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'decision_text',
    ];

    /**
     * Relates back to the meeting.
     */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }
}
