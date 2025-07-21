<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the company that owns the shift.
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
        return route('shifts.show', $this);
    }
}
