<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimeRecordLine extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    /**
     * Get the company that owns the time record line.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the parent time record.
     */
    public function timeRecord()
    {
        return $this->belongsTo(TimeRecord::class);
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('time_record_lines.show', $this);
    }
}
