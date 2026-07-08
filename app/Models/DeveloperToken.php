<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeveloperToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'token_hash',
        'token_encrypted',
    ];

    /**
     * Get the user that owns the developer token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
