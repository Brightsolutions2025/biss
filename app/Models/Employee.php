<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    /**
     * Get the user associated with the employee.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that owns the employee.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the approver of the employee.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the department the employee belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the team the employee belongs to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('employees.show', $this);
    }

    public function employeeShift()
    {
        return $this->hasOne(EmployeeShift::class);
    }
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
    public function overtimeRequests()
    {
        return $this->hasMany(OvertimeRequest::class);
    }
    public function offsetRequests()
    {
        return $this->hasMany(OffsetRequest::class);
    }
    public function outbaseRequests()
    {
        return $this->hasMany(OutbaseRequest::class);
    }
    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }
    public function timeRecords()
    {
        return $this->hasMany(TimeRecord::class);
    }
}
