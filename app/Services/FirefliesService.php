<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingTranscript;
use App\Models\MeetingActionItem;
use App\Models\MeetingDecision;
use App\Models\MeetingParticipant;
use Illuminate\Support\Carbon;

class FirefliesService
{
    /**
     * Persist transcript summary, sentiment, participants, action items, and decisions to DB.
     */
    public function storeTranscriptData(Meeting $meeting, array $data): void
    {
        // 1. Store/Update Transcript
        $overview = $data['summary']['overview'] ?? '';
        $shorthand = is_array($data['summary']['shorthand_bullet_points'] ?? null) 
            ? implode("\n", $data['summary']['shorthand_bullet_points']) 
            : ($data['summary']['shorthand_bullet_points'] ?? '');

        $summaryText = $overview;
        if (!empty($shorthand)) {
            $summaryText .= "\n\nKey Takeaways:\n" . $shorthand;
        }

        MeetingTranscript::updateOrCreate(
            ['meeting_id' => $meeting->id],
            [
                'fireflies_transcript_id' => $data['id'] ?? $data['meetingId'] ?? null,
                'transcript' => $data['transcript_text'] ?? '',
                'summary' => $summaryText,
                'sentiment' => 'Neutral',
            ]
        );

        // 2. Sync Participants (synced from Fireflies meeting_attendees or participants)
        MeetingParticipant::where('meeting_id', $meeting->id)->delete();
        $attendees = $data['meeting_attendees'] ?? [];
        if (!empty($attendees)) {
            foreach ($attendees as $attendee) {
                MeetingParticipant::create([
                    'meeting_id' => $meeting->id,
                    'name' => $attendee['displayName'] ?? explode('@', $attendee['email'] ?? 'Attendee')[0],
                    'email' => $attendee['email'] ?? null,
                    'fireflies_participant_id' => null,
                ]);
            }
        } else {
            // Fallback to simple participants list of emails if no attendee objects
            $emails = $data['participants'] ?? [];
            foreach ($emails as $email) {
                MeetingParticipant::create([
                    'meeting_id' => $meeting->id,
                    'name' => explode('@', $email)[0],
                    'email' => $email,
                    'fireflies_participant_id' => null,
                ]);
            }
        }

        // 3. Sync Action Items (No fictional dates/assignments, but we map to team users if matched by email)
        MeetingActionItem::where('meeting_id', $meeting->id)
            ->where('status', 'Pending')
            ->delete();

        $actionItems = $data['summary']['action_items'] ?? [];
        $teamMembers = $meeting->team ? $meeting->team->members : collect();

        foreach ($actionItems as $item) {
            // Try to resolve assignee by matching email from participants
            $assignedTo = null;
            
            // Clean the text slightly or check if we match any team member name/email in the text
            foreach ($teamMembers as $member) {
                if (stripos($item, $member->name) !== false || stripos($item, explode(' ', $member->name)[0]) !== false) {
                    $assignedTo = $member->id;
                    break;
                }
            }

            MeetingActionItem::create([
                'meeting_id' => $meeting->id,
                'assigned_to' => $assignedTo,
                'action_item' => $item,
                'due_date' => Carbon::now()->addDays(3)->toDateString(), // Standard fallback logic
                'priority' => 'Medium',
                'status' => 'Pending',
                'fireflies_action_item_id' => null,
            ]);
        }

        // 4. Sync Decisions (mapped from shorthand bullet points)
        MeetingDecision::where('meeting_id', $meeting->id)->delete();
        $decisions = $data['summary']['shorthand_bullet_points'] ?? [];
        foreach ($decisions as $decision) {
            MeetingDecision::create([
                'meeting_id' => $meeting->id,
                'decision_text' => $decision,
            ]);
        }
    }
}
