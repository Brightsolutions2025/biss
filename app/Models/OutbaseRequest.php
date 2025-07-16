<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutbaseRequest extends Model
{
    protected $guarded = [];

    /**
     * Get the company that owns the outbase request.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee who made the outbase request.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who approved or rejected the request.
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
        return route('outbase_requests.show', $this);
    }
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
    
    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
