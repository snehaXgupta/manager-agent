<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model
{
    protected $fillable = ['conversation_id', 'role', 'content', 'data_sources', 'structured_response'];

    protected $casts = [
        'data_sources' => 'array',
        'structured_response' => 'array'
    ];

    public function conversation()
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
