<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveSchedule extends Model
{
    // Explicitly tell Laravel the table name (since it’s not the default plural form)
    protected $table = 'active_schedules';

    // Allow mass assignment for these fields
    protected $fillable = ['academicYear', 'semester'];
}
