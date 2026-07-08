<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'risk_level',
        'risk_type',
        'reason',
        'metrics_json',
        'confidence_score',
        'manager_notes',
        'follow_up_action',
        'detected_at',
        'is_resolved',
    ];

    protected $casts = [
        'metrics_json' => 'array',
        'confidence_score' => 'float',
        'detected_at' => 'datetime',
        'is_resolved' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    protected static function booted()
    {
        static::saved(function ($riskAlert) {
            $managerId = $riskAlert->employee?->manager_id;
            if ($managerId) {
                \Illuminate\Support\Facades\Cache::forget("manager_{$managerId}_risk_stats");
            }
        });

        static::deleted(function ($riskAlert) {
            $managerId = $riskAlert->employee?->manager_id;
            if ($managerId) {
                \Illuminate\Support\Facades\Cache::forget("manager_{$managerId}_risk_stats");
            }
        });
    }
}
