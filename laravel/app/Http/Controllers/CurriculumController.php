<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Subject;

class CurriculumController extends Controller
{
    public function upload(Request $request, $courseId)
    {
        $request->validate([
            'curriculum' => 'required|file|mimes:csv,txt',
        ]);

        $course = Course::findOrFail($courseId);

        $file = $request->file('curriculum');
        $text = file_get_contents($file->getRealPath());
        $lines = explode("\n", $text);

        $subjects = [];
        foreach ($lines as $line) {
            $columns = str_getcsv($line);
            $name = trim($columns[0]);
            if ($name) {
                $subject = Subject::firstOrCreate([
                    'course_id' => $course->id,
                    'name' => $name,
                ]);
                $subjects[] = $subject;
            }
        }

        return response()->json($subjects);
    }

    public function getSubjects($courseId)
    {
        $course = Course::findOrFail($courseId);
        return response()->json($course->subjects);
    }
}
