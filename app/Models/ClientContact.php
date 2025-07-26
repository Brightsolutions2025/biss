<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientContact extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Get the company that owns the contact.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the client that owns the contact.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('client-contacts.show', $this);
    }
}
