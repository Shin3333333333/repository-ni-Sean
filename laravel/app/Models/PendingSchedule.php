<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty',
        'faculty_id',
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
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
     protected $appends = ['possible_assignments'];

    /**
     * Accessor for possible assignments.
     * Returns payload[possible_assignments] or falls back to payload[possible_assignments_original]
     */
    public function getPossibleAssignmentsAttribute()
    {
        $payload = $this->payload ?? [];
        $assignments = $payload['possible_assignments'] ?? [];

        if (empty($assignments) && !empty($payload['possible_assignments_original'])) {
            $assignments = $payload['possible_assignments_original'];
        }

        return $assignments;
    }

}