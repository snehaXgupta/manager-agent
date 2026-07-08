<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'description'];

    /**
     * Get the users in this department.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
