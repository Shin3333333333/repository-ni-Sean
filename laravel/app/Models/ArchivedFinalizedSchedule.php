<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchivedFinalizedSchedule extends Model
{
    use HasFactory;

    protected $table = 'archived_finalized_schedules';

    protected $fillable = [
        'user_id',
        'batch_id',
        'faculty_id',
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
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}


