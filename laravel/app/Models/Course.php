<?php

namespace App\Models;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    class Course extends Model
    {
        use HasFactory;
        protected $fillable = ['name', 'code'];  // Adjust based on your migration
        // Add relationships if needed, e.g., public function subjects() { return $this->hasMany(Subject::class); }
    }

