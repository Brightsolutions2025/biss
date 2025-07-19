<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimeRecord extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    /**
     * Get the company that owns the time record.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee associated with the time record.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the payroll period associated with the time record.
     */
    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    /**
     * Get the lines (daily records) associated with the time record.
     */
    public function lines()
    {
        return $this->hasMany(TimeRecordLine::class);
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('time_records.show', $this);
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
