<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyUser extends Pivot
{
    protected $table = 'company_user';

    protected $guarded = [];

    /**
     * Get the company for this pivot entry.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user for this pivot entry.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Route model binding or resource route helper (if needed).
     */
    public function path()
    {
        return route('company_users.show', [$this->company_id, $this->user_id]);
    }
}
