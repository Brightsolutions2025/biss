<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeShift extends Model
{
    protected $guarded = [];

    /**
     * Get the company that owns the employee shift.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee assigned to this shift.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the shift details.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('employee_shifts.show', $this);
    }
}
