<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',        // course name
        'section',
        'year',        // year level
        'department',
        'adviser',
        'curriculum',
    ];

   public function subjects() {
        return $this->hasMany(Subject::class);
    }
}
