<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'name',
        'email',
        'fireflies_participant_id',
    ];

    /**
     * Relates to the meeting this participant attended.
     */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }
}
