<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;


class Ticket extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'attachments'       => 'array',
        'due_at'            => 'datetime',
        'resolved_at'       => 'datetime',
        'approved_at'       => 'datetime',
        'requires_approval' => 'boolean',
    ];

    /**
     * Generate a sequential ticket number per company.
     * Format example: TCK-2025-0001
     */
    public static function generateTicketNumber()
    {
        $year = date('Y');

        // Lock the table during number generation to prevent race conditions
        $lastTicket = self::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (!$lastTicket) {
            $nextNumber = 1;
        } else {
            // Extract the number part from ticket_number
            $lastNumber = (int) substr($lastTicket->ticket_number, -4);
            $nextNumber = $lastNumber + 1;
        }

        return sprintf("TCK-%s-%04d", $year, $nextNumber);
    }

    /**
     * Relationships
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function ticketType()
    {
        return $this->belongsTo(TicketType::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function path()
    {
        return route('tickets.show', $this);
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }
    public function assignedToUser() {
    return $this->belongsTo(User::class, 'assigned_to');
    }
    public function assignedByUser() {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
    