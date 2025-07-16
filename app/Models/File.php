<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = ['file_path', 'file_name'];

    public function fileable()
    {
        return $this->morphTo();
    }
}
