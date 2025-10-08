<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CoursesController extends Controller
{
    public function index()
    {
        // include curriculum relationship for display
        return Course::with('curriculum')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'required|string|max:50',
            'students' => 'required|integer|min:1',
            'curriculum_id' => 'nullable|integer|exists:curriculums,id',
        ]);

        $course = Course::create($validated);

        return response()->json([
            'message' => 'Course added successfully',
            'course' => $course->load('curriculum')
        ]);
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'year' => 'sometimes|string|max:50',
            'students' => 'sometimes|integer|min:1',
            'curriculum_id' => 'nullable|integer|exists:curriculums,id',
        ]);

        $course->update($validated);

        return response()->json([
            'message' => 'Course updated successfully',
            'course' => $course->load('curriculum')
        ]);
    }

    public function destroy($id)
    {
        Course::destroy($id);
        return response()->json(['message' => 'Course deleted successfully']);
    }
}
