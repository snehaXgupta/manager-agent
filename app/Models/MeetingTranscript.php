<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingTranscript extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'transcript',
        'summary',
        'sentiment',
        'fireflies_transcript_id',
    ];

    /**
     * Relates back to the meeting.
     */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }
}
