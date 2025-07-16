<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    protected $guarded = [];
    protected $dates = ['start_date', 'end_date'];
    protected $casts = [
        'dtr_submission_due_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the company that owns the payroll period.
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
        return route('payroll_periods.show', $this);
    }
}
