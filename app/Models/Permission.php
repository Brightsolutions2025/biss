<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permission extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    /**
     * Get the company that owns the permission.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The roles that have this permission.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class)
                    ->withTimestamps()
                    ->withPivot('company_id'); // If you're handling soft deletes on the pivot
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('permissions.show', $this);
    }
}
