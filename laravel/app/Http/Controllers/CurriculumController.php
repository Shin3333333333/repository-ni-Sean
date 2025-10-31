<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Curriculum;
use App\Models\Subject;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class CurriculumController extends Controller
{
    // 🔹 List all curriculums
    public function index()
    {
        return response()->json(Curriculum::all());
    }

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

    Log::info("📘 Uploading curriculum file: {$filename}");

    $spreadsheet = IOFactory::load($file->getPathname());
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $currentYearLevel = null;
    $currentSemesterId = null;
    $headerIndexes = [];
    $skipNextRow = false;

    foreach ($rows as $rowIndex => $row) {
        if ($skipNextRow) {
            $skipNextRow = false;
            continue;
        }

        if (!array_filter($row)) continue; // Skip empty rows

        $firstCell = trim($row[0] ?? '');
        if ($firstCell === '') continue;

        // Detect Year Level
        if (preg_match('/(first|second|third|fourth|fifth)\s*year/i', $firstCell, $match)) {
            $map = [
                'first' => '1st Year',
                'second' => '2nd Year',
                'third' => '3rd Year',
                'fourth' => '4th Year',
                'fifth' => '5th Year',
            ];
            $currentYearLevel = $map[strtolower($match[1])] ?? $firstCell;
            Log::info("🧭 Detected Year Level: {$currentYearLevel} (Row {$rowIndex})");
            continue;
        }

        // Detect Semester
        $firstCellLower = strtolower($firstCell);
        if (preg_match('/\b(1st|first|1)\b.*semester/i', $firstCellLower)) {
            $currentSemesterId = 1;
        } elseif (preg_match('/\b(2nd|second|2)\b.*semester/i', $firstCellLower)) {
            $currentSemesterId = 2;
        } elseif (preg_match('/summer/i', $firstCellLower)) {
            $currentSemesterId = 3;
        }

        if ($currentSemesterId && preg_match('/semester|summer/i', $firstCellLower)) {
            Log::info("📅 Detected Semester: '{$firstCell}' → ID {$currentSemesterId} (Row {$rowIndex})");
            $headerIndexes = [];
            continue;
        }

        // Detect & Merge Header Rows
        if (empty($headerIndexes)) {
            $normalizedRow = array_map(fn($v) => strtolower(trim($v ?? '')), $row);
            $nextRow = isset($rows[$rowIndex + 1]) ? array_map(fn($v) => strtolower(trim($v ?? '')), $rows[$rowIndex + 1]) : [];
            $combined = [];
            for ($i = 0; $i < max(count($normalizedRow), count($nextRow)); $i++) {
                $combined[$i] = trim(($normalizedRow[$i] ?? '') . ' ' . ($nextRow[$i] ?? ''));
            }
            if (empty(array_filter($combined))) $combined = $normalizedRow;

            $possibleHeaders = implode(' ', $combined);
            if (preg_match('/course.*code/', $possibleHeaders) && preg_match('/description|title|subject/', $possibleHeaders)) {
                foreach ($combined as $i => $colName) {
                    if (preg_match('/code/', $colName)) $headerIndexes['code'] = $i;
                    if (preg_match('/description|title|subject/', $colName)) $headerIndexes['title'] = $i;
                    if (preg_match('/lec|lecture/', $colName)) $headerIndexes['lec'] = $i;
                    if (preg_match('/lab|laboratory/', $colName)) $headerIndexes['lab'] = $i;
                    if (preg_match('/pre.*req/', $colName)) $headerIndexes['pre'] = $i;
                    if (preg_match('/unit/', $colName) && !isset($headerIndexes['lec'])) {
                        // Only use Units column if Lec not found
                        $headerIndexes['lec'] = $i;
                        $headerIndexes['lab'] = null;
                    }
                }
                Log::info("🧾 Header detected at row {$rowIndex}: " . json_encode($headerIndexes));
                $skipNextRow = true;
                continue;
            }
        }

        if (empty($headerIndexes) || !$currentYearLevel) continue;

        if (!$currentSemesterId) $currentSemesterId = 1;

        $rowString = strtolower(implode(' ', $row));
        if (str_contains($rowString, 'total unit') || str_contains($rowString, 'course code') || str_contains($rowString, 'description')) {
            continue;
        }

        $code = trim($row[$headerIndexes['code']] ?? '');
        $title = trim($row[$headerIndexes['title']] ?? '');
        if ($code === '' || $title === '') continue;

        if (!preg_match('/^[A-Z]{1,6}[- ]?\d{1,4}[A-Z-]*$/i', $code)) continue;

        // Handle units
        $lecUnits = isset($headerIndexes['lec']) ? (float)($row[$headerIndexes['lec']] ?? 0) : 0;
        $labUnits = isset($headerIndexes['lab']) && $headerIndexes['lab'] !== null ? (float)($row[$headerIndexes['lab']] ?? 0) : 0;
        $totalUnits = $lecUnits + $labUnits;

        $preReqRaw = $row[$headerIndexes['pre']] ?? '';
        $preReq = trim($preReqRaw);
        if ($preReq === '' || $preReq === '-') $preReq = 'None';

        try {
            Subject::create([
                'curriculum_id' => $curriculum->id,
                'course_id'     => null,
                'year_level'    => $currentYearLevel,
                'semester_id'   => $currentSemesterId,
                'subject_code'  => $code,
                'subject_title' => $title,
                'lec_units'     => $lecUnits,
                'lab_units'     => $labUnits,
                'total_units'   => $totalUnits,
                'pre_requisite' => $preReq,
            ]);

            Log::info("✅ Added: {$code} – {$title} ({$currentYearLevel}, Sem {$currentSemesterId})");
        } catch (\Exception $e) {
            Log::error("❌ Failed to insert subject at row {$rowIndex}: " . $e->getMessage());
        }
    }

    Log::info("🎓 Curriculum import complete for {$curriculum->name}");

    return response()->json([
        'message' => 'Curriculum uploaded and subjects stored successfully.',
        'curriculum' => $curriculum,
    ]);
}



    // 🔹 List subjects by curriculum
    public function subjects($curriculum_id)
    {
        return response()->json(
            Subject::where('curriculum_id', $curriculum_id)->get()
        );
    }

    // 🔹 Update curriculum (e.g., rename)
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $curriculum = Curriculum::findOrFail($id);
        $curriculum->name = $validated['name'];
        $curriculum->save();

        return response()->json([
            'message' => 'Curriculum updated successfully.',
            'curriculum' => $curriculum,
        ]);
    }

    // 🔹 Delete curriculum and its subjects
    public function destroy($id)
    {
        $curriculum = Curriculum::findOrFail($id);

        // Delete all subjects under this curriculum
        Subject::where('curriculum_id', $curriculum->id)->delete();

        // Delete the curriculum record
        $curriculum->delete();

        return response()->json([
            'message' => 'Curriculum deleted successfully.'
        ]);
    }
}
