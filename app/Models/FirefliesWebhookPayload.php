<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirefliesWebhookPayload extends Model
{
    protected $table = 'fireflies_webhook_payloads';

    protected $fillable = [
        'fireflies_meeting_id',
        'event_type',
        'payload',
        'processed',
        'processed_at',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];
}
