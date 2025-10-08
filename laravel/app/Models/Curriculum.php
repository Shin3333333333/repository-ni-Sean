<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
    use HasFactory;
    protected $table = 'curriculums'; // 👈 add this line
 
    protected $fillable = ['name',  'file_path'];


   public function index()
{
    return Course::with(['curriculum:id,filename', 'subjects:id,name,course_id'])->get();
}
// In Curriculum.php model
public function getNameAttribute() {
    return $this->filename;
}


}
