<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the company that owns the overtime request.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee who submitted the overtime request.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the approver (user) for the overtime request.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('overtime_requests.show', $this);
    }
    protected static function booted()
    {
        static::creating(function ($overtime) {
            if (! $overtime->expires_at && $overtime->date && $overtime->employee_id && $overtime->company_id) {
                $employee = \App\Models\Employee::find($overtime->employee_id);

                if ($employee && $employee->ot_not_convertible_to_offset) {
                    // Immediately expire overtime if it's not convertible to offset
                    $overtime->expires_at = \Carbon\Carbon::parse($overtime->date);
                } else {
                    $company = \App\Models\Company::find($overtime->company_id);
                    $days    = $company->offset_valid_after_days ?? 90;

                    $overtime->expires_at = \Carbon\Carbon::parse($overtime->date)->addDays($days);
                }
            }
        });

        static::updating(function ($overtime) {
            if ($overtime->isDirty('date') && $overtime->employee_id && $overtime->company_id) {
                $employee = \App\Models\Employee::find($overtime->employee_id);

                if ($employee && $employee->ot_not_convertible_to_offset) {
                    $overtime->expires_at = \Carbon\Carbon::parse($overtime->date);
                } else {
                    $company = \App\Models\Company::find($overtime->company_id);
                    $days    = $company->offset_valid_after_days ?? 90;

                    $overtime->expires_at = \Carbon\Carbon::parse($overtime->date)->addDays($days);
                }
            }
        });
    }
    public function offsetRequests()
    {
        return $this->belongsToMany(OffsetRequest::class, 'offset_overtime')
                    ->withPivot('used_hours')
                    ->withTimestamps();
    }
    public function getRemainingHoursAttribute()
    {
        $used = $this->offsetRequests()->sum('offset_overtime.used_hours');
        return $this->number_of_hours - $used;
    }
    public function offsetOvertimes()
    {
        return $this->hasMany(OffsetOvertime::class, 'overtime_request_id');
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
