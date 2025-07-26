<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active'    => 'boolean',
        'rating'       => 'integer',
        'credit_limit' => 'decimal:2',
    ];

    /**
     * Get the company that owns the client.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the contacts for the client.
     */
    public function contacts()
    {
        return $this->hasMany(ClientContact::class);
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('clients.show', $this);
    }
}
