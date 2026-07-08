<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'scheduled_at',
        'meeting_date',
        'meeting_time',
        'duration',
        'meeting_link',
        'status',
        'created_by',
        'meeting_notes',
        'team_id',
        'manager_id',
        'fireflies_meeting_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'meeting_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($meeting) {
            if ($meeting->meeting_date && $meeting->meeting_time) {
                $dateStr = \Illuminate\Support\Carbon::parse($meeting->meeting_date)->toDateString();
                $meeting->scheduled_at = \Illuminate\Support\Carbon::parse($dateStr . ' ' . $meeting->meeting_time);
            }
        });
    }

    /**
     * Relates to the team this meeting is scheduled for.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Relates to the manager who scheduled the meeting.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Relates to the user who created the meeting.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relates to the meeting transcript.
     */
    public function transcript()
    {
        return $this->hasOne(MeetingTranscript::class, 'meeting_id');
    }

    /**
     * Relates to the action items of this meeting.
     */
    public function actionItems()
    {
        return $this->hasMany(MeetingActionItem::class, 'meeting_id');
    }

    /**
     * Relates to the decisions made in this meeting.
     */
    public function decisions()
    {
        return $this->hasMany(MeetingDecision::class, 'meeting_id');
    }

    /**
     * Relates to the participants of this meeting synced from Fireflies.
     */
    public function meetingParticipants()
    {
        return $this->hasMany(MeetingParticipant::class, 'meeting_id');
    }
}
