<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = [];

    /**
     * The company this role belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Users assigned to this role.
     */
    public function users()
    {
        return $this->belongsToMany(User::class)
                    ->withTimestamps()
                    ->withPivot('company_id');
    }

    /**
     * Permissions assigned to this role.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class)
                    ->withTimestamps()
                    ->withPivot('company_id');
    }

    /**
     * Route path helper.
     */
    public function path()
    {
        return route('roles.show', $this);
    }

    public function allowTo($permission, $companyId = null)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $companyId = $companyId ?? auth()->user()->preference->company_id;

        // Use attach instead of sync to avoid detaching existing entries
        $this->permissions()->attach($permission->id, ['company_id' => $companyId]);
    }
}
