<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model {
    use HasFactory;
    protected $fillable = ['subject', 'start_time', 'end_time', 'room_id', 'professor_id'];  // e.g., 'Monday'
    protected $casts = ['start_time' => 'datetime:H:i', 'end_time' => 'datetime:H:i'];

    public function room() { return $this->belongsTo(Room::class); }
    public function professor() { return $this->belongsTo(Professor::class); }
    public function errors() { return $this->hasMany(ErrorLog::class); }
}
