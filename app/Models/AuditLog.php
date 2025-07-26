<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'company_id',
        'action',
        'model_type',
        'model_id',
        'changes',
        'performed_by',
        'ip_address',
        'user_agent',
        'context',
        'origin_screen',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }

}
