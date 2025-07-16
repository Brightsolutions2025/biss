<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class OffsetOvertime extends Pivot
{
    protected $table = 'offset_overtime';

    protected $guarded = [];

    public function offsetRequest()
    {
        return $this->belongsTo(OffsetRequest::class);
    }

    public function overtimeRequest()
    {
        return $this->belongsTo(OvertimeRequest::class);
    }
}
