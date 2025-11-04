<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'curriculum_id',
        'course_id',
        'year_level',
        'semester_id',
        'subject_code',
        'subject_title',
        'lec_units',
        'lab_units',
        'total_units',
        'pre_requisite',
      
    ];

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }


}
