<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimePreApproval extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'approval_date' => 'date',
        'estimated_number_of_hours' => 'decimal:2',
    ];

    /**
     * Get the company that owns the overtime pre-approval.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee who filed the overtime pre-approval.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the approver user of the overtime pre-approval.
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
        return route('overtime_pre_approvals.show', $this);
    }

    /**
     * Optional file relationship if you later add attachments.
     */
    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }
}