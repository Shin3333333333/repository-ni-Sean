<?php

namespace App\Models;

  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Model;
  class ErrorLog extends Model
  {
      use HasFactory;
      protected $fillable = ['schedule_id', 'error_type', 'description', 'ai_suggestion'];
      public function schedule()
      {
          return $this->belongsTo(Schedule::class);
      }
  }
