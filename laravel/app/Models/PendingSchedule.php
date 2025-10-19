<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
    'faculty',
    'subject',
    'time',
    'classroom',
    'course_code',
    'course_section',
    'units',
    'academicYear',
    'semester',
    'status',
    'user_id',
    'batch_id',
];

}
