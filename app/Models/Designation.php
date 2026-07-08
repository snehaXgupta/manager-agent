<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    protected $fillable = ['name', 'description'];

    /**
     * Get the users holding this designation.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
