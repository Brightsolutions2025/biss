<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the department that owns the team.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the employees that belong to the team.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('teams.show', $this);
    }
}
