<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    public const TYPE_VACATION = 'vacation';
    public const TYPE_EMERGENCY = 'emergency';

    protected $guarded = [];

    /**
     * Get the company that owns the leave request.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee who filed the leave request.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the approver (User) of the leave request.
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
        return route('leave_requests.show', $this);
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function getLeaveTypeLabelAttribute(): string
    {
        return config("leave.types.{$this->leave_type}.label", ucfirst((string) $this->leave_type));
    }

    public function isEmergencyLeave(): bool
    {
        return $this->leave_type === self::TYPE_EMERGENCY;
    }
}

