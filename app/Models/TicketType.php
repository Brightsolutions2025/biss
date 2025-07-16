<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    protected $guarded = [];

    /**
     * Get the company that owns this ticket type.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the tickets associated with this ticket type.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('ticket_types.show', $this);
    }
}
