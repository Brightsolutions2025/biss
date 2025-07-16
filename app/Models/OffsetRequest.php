<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OffsetRequest extends Model
{
    protected $guarded = [];

    /**
     * Get the company that owns the offset request.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee who made the offset request.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who approved the request.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the overtime requests linked to this offset request.
     */
    public function overtimeRequests()
    {
        return $this->belongsToMany(OvertimeRequest::class, 'offset_overtime')
                    ->withPivot('used_hours')
                    ->withTimestamps();
    }

    public function offsetOvertimes()
    {
        return $this->hasMany(OffsetOvertime::class);
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('offset_requests.show', $this);
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
