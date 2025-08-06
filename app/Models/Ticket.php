<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'attachments'  => 'array',
        'due_at'       => 'datetime',
        'resolved_at'  => 'datetime',
        'approved_at'  => 'datetime',
        'requires_approval' => 'boolean',
    ];

    /**
     * Get the company that owns the ticket.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the department the ticket belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the team the ticket belongs to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the ticket type.
     */
    public function ticketType()
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * Get the user who created the ticket.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user assigned to this ticket.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who approved the ticket.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('tickets.show', $this);
    }
}
