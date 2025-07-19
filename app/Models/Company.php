<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function path()
    {
        return route('companies.show', $this);
    }
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
    public function roles()
    {
        return $this->hasMany(Role::class);
    }
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
    public function departments()
    {
        return $this->hasMany(Department::class);
    }
    public function teams()
    {
        return $this->hasMany(Team::class);
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
    public function payrollPeriods()
    {
        return $this->hasMany(PayrollPeriod::class);
    }
}
