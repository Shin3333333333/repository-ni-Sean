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
        return Course::with('curriculum')->get();
    }

    /**
     * Store a new course and clone subjects from the curriculum if provided
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

        // Clone subjects from curriculum if applicable
        if ($course->curriculum_id) {
            $subjects = Subject::where('curriculum_id', $course->curriculum_id)
                ->whereNull('course_id') // avoid re-cloning existing course-linked subjects
                ->get();

            foreach ($subjects as $subject) {
                Subject::create([
                    'curriculum_id' => $subject->curriculum_id,
                    'course_id'     => $course->id, // now linked to this course
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

        // Update course info
        $course->update($validated);

        // ✅ If subjects are included in the request, update them too
        if (!empty($validated['subjects'])) {
            foreach ($validated['subjects'] as $subjectData) {
                if (isset($subjectData['id'])) {
                    // Update existing subject
                    Subject::where('id', $subjectData['id'])
                        ->where('course_id', $course->id)
                        ->update($subjectData);
                }
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

    // Delete only subjects linked to this course
    Subject::where('course_id', $course->id)->delete();

    // Delete the course
    $course->delete();

    return response()->json([
        'message' => 'Course and its associated subjects deleted successfully'
    ]);
}


}
