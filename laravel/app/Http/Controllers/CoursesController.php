<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Course;
use Illuminate\Http\Request;

class CoursesController extends Controller
{
    /**
     * Display all courses with their linked curriculum
     */
    public function index()
    {
        return Course::with('curriculum', 'subjects')->get();
    }

    /**
     * Store a new course and clone subjects only for its year level
     */
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'year' => 'required|string|max:50',
        'students' => 'required|integer|min:1',
        'curriculum_id' => 'nullable|integer|exists:curriculums,id',
    ]);

    // Create the new course
    $course = Course::create($validated);

    // Clone subjects from curriculum only for the course's year
    if ($course->curriculum_id) {
        $subjects = Subject::where('curriculum_id', $course->curriculum_id)
            ->whereNull('course_id')
            ->where('year_level', $course->year) // only current year subjects
            ->get();

        foreach ($subjects as $subject) {
            // ✅ Check if this subject already exists for the same course
            $exists = Subject::where('course_id', $course->id)
                ->whereRaw('LOWER(TRIM(subject_code)) = ?', [strtolower(trim($subject->subject_code))])
                ->whereRaw('LOWER(TRIM(subject_title)) = ?', [strtolower(trim($subject->subject_title))])
                ->exists();

            if ($exists) continue; // skip duplicate

            Subject::create([
                'curriculum_id' => $subject->curriculum_id,
                'course_id'     => $course->id,
                'year_level'    => $subject->year_level,
                'semester_id'   => $subject->semester_id,
                'subject_code'  => trim($subject->subject_code),
                'subject_title' => trim($subject->subject_title),
                'lec_units'     => $subject->lec_units,
                'lab_units'     => $subject->lab_units,
                'total_units'   => $subject->total_units,
                'pre_requisite' => $subject->pre_requisite ?? 'None',
            ]);
        }
    }

    return response()->json([
        'message' => 'Course added successfully',
        'course' => $course->load('curriculum', 'subjects')
    ]);
}


    /**
     * Show a specific course with its curriculum and related subjects
     */
    public function show($id)
    {
        $course = Course::with(['curriculum', 'subjects'])->findOrFail($id);

        return response()->json([
            'course' => $course
        ]);
    }

    /**
     * Update a course
     */
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'year' => 'sometimes|string|max:50',
            'students' => 'sometimes|integer|min:1',
            'curriculum_id' => 'nullable|integer|exists:curriculums,id',
            'subjects' => 'nullable|array'
        ]);

        // Check if curriculum changed
        $curriculumChanged = isset($validated['curriculum_id']) && $validated['curriculum_id'] != $course->curriculum_id;

        // Update course info
        $course->update($validated);

        // If subjects are included, update existing ones
        if (!empty($validated['subjects'])) {
            foreach ($validated['subjects'] as $subjectData) {
                if (isset($subjectData['id'])) {
                    Subject::where('id', $subjectData['id'])
                        ->where('course_id', $course->id)
                        ->update($subjectData);
                }
            }
        }

        // If curriculum changed, re-clone only subjects for the course's year
        if ($curriculumChanged && $course->curriculum_id) {
            // Delete old subjects linked to this course
            Subject::where('course_id', $course->id)->delete();

            // Clone new subjects from the new curriculum for the course's year
            $subjects = Subject::where('curriculum_id', $course->curriculum_id)
                ->whereNull('course_id')
                ->where('year_level', $course->year)
                ->get();

            foreach ($subjects as $subject) {
                Subject::create([
                    'curriculum_id' => $subject->curriculum_id,
                    'course_id'     => $course->id,
                    'year_level'    => $subject->year_level,
                    'semester_id'   => $subject->semester_id,
                    'subject_code'  => $subject->subject_code,
                    'subject_title' => $subject->subject_title,
                    'units'         => $subject->units,
                    'hours'         => $subject->hours,
                    'pre_requisite' => $subject->pre_requisite ?? 'None',
                    'type'          => $subject->type ?? 'Major',
                ]);
            }
        }

        return response()->json([
            'message' => 'Course updated successfully',
            'course' => $course->load('curriculum', 'subjects')
        ]);
    }

    /**
     * Delete a course and its linked subjects
     */
 public function destroy($id)
{
    $course = Course::findOrFail($id);

    // Force delete all related subjects just in case
    Subject::where('course_id', $course->id)->forceDelete();

    // Delete the course itself
    $course->delete();

    return response()->json([
        'message' => 'Course and all associated subjects deleted successfully'
    ]);
}

}
