<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['name', 'course_id', 'year_id'];
    public function course() { return $this->belongsTo(Course::class); }
    public function year() { return $this->belongsTo(Year::class); }
    public function schedules() { return $this->hasMany(Schedule::class); }  // If linking subjects to schedules
}
