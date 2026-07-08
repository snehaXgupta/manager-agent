<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'manager_id',
        'report_type',
        'period_start',
        'period_end',
        'metrics_json',
        'ai_insights_json',
        'manager_score',
        'generated_at',
    ];

    protected $casts = [
        'metrics_json' => 'array',
        'ai_insights_json' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
    ];

    /**
     * Relates to the manager this report was created for.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
