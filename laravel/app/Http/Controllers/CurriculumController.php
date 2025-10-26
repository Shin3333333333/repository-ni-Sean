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
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls',
    ]);

    $file = $request->file('file');
    $filename = time() . '_' . $file->getClientOriginalName();
    $path = $file->storeAs('curriculums', $filename);

    $curriculum = Curriculum::create([
        'name' => pathinfo($filename, PATHINFO_FILENAME),
        'file_path' => $path,
    ]);

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $currentSemesterId = null;
    $currentYearLevel = null;

    $headerKeywords = ['subject code', 'subject title', 'lec units', 'lab units', 'total units', 'prerequisite'];

    foreach ($rows as $row) {
        if (!array_filter($row)) continue;

        $firstCell = trim($row[0] ?? '');

        // Detect year & semester row
        if (preg_match('/(\d+(st|nd|rd|th)\sYear)\s–\s(\d+(st|nd|rd|th)\sSemester)/i', $firstCell, $matches)) {
            $currentYearLevel = $matches[1];

            $semesterText = strtolower($matches[3]);
            if (str_contains($semesterText, '1st')) {
                $currentSemesterId = 1;
            } elseif (str_contains($semesterText, '2nd')) {
                $currentSemesterId = 2;
            } else {
                $currentSemesterId = null;
            }
            continue;
        }

        // Skip header rows
        $isHeaderRow = false;
        foreach ($row as $cell) {
            if ($cell && in_array(strtolower(trim($cell)), $headerKeywords)) {
                $isHeaderRow = true;
                break;
            }
        }
        if ($isHeaderRow) continue;

        // ✅ Insert subject (map LEC/LAB/TOTAL properly)
        Subject::create([
            'curriculum_id' => $curriculum->id,
            'course_id'     => null,
            'year_level'    => $currentYearLevel,
            'semester_id'   => $currentSemesterId,
            'subject_code'  => $row[0] ?? null,
            'subject_title' => $row[1] ?? null,
            'lec_units'     => isset($row[2]) ? (int)$row[2] : 0,
            'lab_units'     => isset($row[3]) ? (int)$row[3] : 0,
            'total_units'   => isset($row[4]) ? (int)$row[4] : 0,
            'pre_requisite' => $row[5] ?? 'None',
        ]);
    }

    return response()->json([
        'message' => 'Curriculum uploaded and subjects stored successfully.',
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
