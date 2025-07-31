<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_date'           => 'date',
        'end_date'             => 'date',
        'completion_date_actual' => 'date',
        'budget'               => 'decimal:2',
        'budget_buffer'        => 'decimal:2',
        'locked_budget'        => 'boolean',
        'tags'                 => 'array',
    ];

    /**
     * Get the company that owns the project.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the client that owns the project.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user assigned as the project manager.
     */
    public function projectManager()
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    /**
     * Get the user who created the project.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Route model binding or resource route helper.
     */
    public function path()
    {
        return route('projects.show', $this);
    }
}
