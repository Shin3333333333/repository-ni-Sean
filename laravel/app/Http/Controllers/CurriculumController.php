<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Curriculum;
use App\Models\Subject;
use App\Models\Semester;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CurriculumController extends Controller
{
    // Get all curricula
    public function index()
    {
        $curricula = Curriculum::all();
        return response()->json($curricula);
    }

    // Upload a curriculum XLSX and store subjects
    public function store(Request $request)
    {
        // Validate uploaded file
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('curriculums', $filename);

        // Save curriculum record
        $curriculum = Curriculum::create([
            'name' => pathinfo($filename, PATHINFO_FILENAME),
            'file_path' => $path,
        ]);

        // Parse XLSX
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $currentSemesterId = null;

        foreach ($rows as $index => $row) {
            // Skip completely empty rows
            if (!array_filter($row)) continue;

            // Detect semester row
            $possibleSemester = trim($row[0] ?? '');
            $semester = Semester::where('name', $possibleSemester)->first();
            if ($semester) {
                $currentSemesterId = $semester->id;
                continue; // skip this row, it's a semester header
            }

            // Skip table header rows
            if (str_contains(strtolower($row[1] ?? ''), 'subject code')) continue;

            // Insert subject
            Subject::create([
                'curriculum_id' => $curriculum->id,
                'course_id'     => null, // assign later when course is created
                'year_level'    => $row[0] ?? null,
                'semester_id'   => $currentSemesterId,
                'subject_code'  => $row[1] ?? null,
                'subject_title' => $row[2] ?? null,
                'units'         => isset($row[3]) ? (int)$row[3] : null,
                'hours'         => isset($row[4]) ? (int)$row[4] : null,
                'pre-requisite' => $row[5] ?? null,
                'type'          => $row[6] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Curriculum uploaded and subjects stored successfully',
            'curriculum' => $curriculum,
        ]);
    }

    // Get subjects for a curriculum
    public function subjects($curriculum_id)
    {
        $subjects = Subject::where('curriculum_id', $curriculum_id)->get();
        return response()->json($subjects);
    }
}
