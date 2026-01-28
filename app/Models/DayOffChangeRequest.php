<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayOffChangeRequest extends Model
{
    use HasFactory;

    protected $table = 'day_off_change_requests';

    protected $fillable = [
        'company_id',
        'employee_id',
        'reason_type',
        'extension_date',
        'time_start',
        'time_end',
        'number_of_hours',
        'old_date',
        'new_date',
        'reason',
        'status',
        'approver_id',
        'approval_date',
        'rejection_reason',
    ];

    protected $casts = [
        'extension_date' => 'date',
        'old_date'       => 'date',
        'new_date'       => 'date',
        'approval_date'  => 'date',
        'number_of_hours'=> 'decimal:2',
        'time_start'     => 'datetime:H:i',
        'time_end'       => 'datetime:H:i',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    /**
     * Company that owns the request
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Employee who filed the request
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Approver (user)
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Route helper
     */
    public function path()
    {
        return route('day_off_change_requests.show', $this);
    }
}
