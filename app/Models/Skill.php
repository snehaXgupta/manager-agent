<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = ['name'];

    /**
     * Get the users who possess this skill.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'employee_skill')
                    ->withPivot('proficiency')
                    ->withTimestamps();
    }
}
