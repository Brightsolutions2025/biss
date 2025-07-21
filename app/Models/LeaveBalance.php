<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the company that owns the leave balance.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee that this leave balance belongs to.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('leave-balances.show', $this);
    }
}
