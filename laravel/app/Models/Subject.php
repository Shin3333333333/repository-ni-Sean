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
        'units',
        'hours',
        'pre_requisite',
        'type'
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
