<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $guarded = [];

    /**
     * Get the company that owns the department.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('departments.show', $this);
    }
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
    public function head()
    {
        return $this->belongsTo(User::class, 'head_id');
    }
}
