<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the company that owns the time log.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the payroll period that this time log belongs to.
     */
    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('time_logs.show', $this);
    }
}
