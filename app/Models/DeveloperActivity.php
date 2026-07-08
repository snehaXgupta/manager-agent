<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeveloperActivity extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'developer_activities';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'platform',
        'event_type',
        'repository',
        'reference_id',
        'details_json',
        'occurred_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'details_json' => 'array',
        'occurred_at' => 'datetime',
    ];

    /**
     * Get the user associated with this developer activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
