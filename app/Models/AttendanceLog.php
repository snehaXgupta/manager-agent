<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'is_early_exit',
    ];

    protected $casts = [
        'is_early_exit' => 'boolean',
        'date' => 'date',
    ];

    /**
     * Relates to the user this attendance log belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
