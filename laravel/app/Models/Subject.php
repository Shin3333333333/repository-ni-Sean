<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['curriculum_id', 'code', 'name', 'units', 'semester', 'year_level'];

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }
}
