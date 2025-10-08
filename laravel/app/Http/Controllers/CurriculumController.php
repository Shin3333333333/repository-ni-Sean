<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Curriculum;
use App\Models\Subject;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;

class CurriculumController extends Controller
{
    // Upload a curriculum XLSX
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('curriculums', $filename);

        // Save curriculum record
        $curriculum = Curriculum::create([
                'name' => $filename,
                'file_path' => $path,
            ]);


        // Parse XLSX
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Assuming first row is headers, skip it
        foreach($rows as $index => $row) {
            if ($index === 0) continue;

            Subject::create([
                'curriculum_id' => $curriculum->id, // link to curriculum
                'code' => $row[0] ?? null,          // Subject Code
                'name' => $row[1] ?? null,          // Title
                'units' => $row[2] ?? null,         // Units
                'semester' => $row[3] ?? null,      // Semester
                'year_level' => $row[4] ?? null,    // Year Level
            ]);
        }

        return response()->json([
            'message' => 'Curriculum uploaded and subjects stored',
            'curriculum' => $curriculum,
        ]);
    }

    // Get subjects for a curriculum (for frontend)
    public function subjects($curriculum_id)
    {
        $subjects = Subject::where('curriculum_id', $curriculum_id)->get();
        return response()->json($subjects);
    }
}
