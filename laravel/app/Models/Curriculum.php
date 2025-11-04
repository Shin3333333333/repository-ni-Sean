<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
    use HasFactory;

    // Specify the actual table name
    protected $table = 'curriculums';

    // Mass assignable fields
    protected $fillable = ['name', 'file_path'];

    // Optional: if you want to use an accessor for display
    public function getFilenameAttribute()
    {
        return $this->name; // returns the stored filename
    }

    // Relationship example: one curriculum has many subjects
    public function subjects()
{
    return $this->hasMany(Subject::class, 'curriculum_id', 'curriculum_id');
}

}
