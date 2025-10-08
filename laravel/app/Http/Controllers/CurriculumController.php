<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Curriculum;
use Illuminate\Support\Facades\Storage;

class CurriculumController extends Controller
{
    // 🧾 Get all curriculums
    public function index()
    {
        return Curriculum::all();
    }

    // 📤 Upload and save a curriculum file
    public function store(Request $request)
    {
        // Validate that a file is present
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,xls|max:2048',
        ]);

        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();

        // Save file to storage/app/public/curriculums
        $path = $file->storeAs('curriculums', $filename, 'public');

        // Create DB record
        $curriculum = Curriculum::create([
            'name' => $file->getClientOriginalName(),
            'file_path' => $path,
        ]);

        return response()->json([
            'message' => 'Curriculum uploaded successfully!',
            'data' => $curriculum
        ]);
    }

    // ❌ Delete a curriculum
    public function destroy($id)
    {
        $curriculum = Curriculum::findOrFail($id);

        // Delete file if exists
        if ($curriculum->file_path && Storage::disk('public')->exists($curriculum->file_path)) {
            Storage::disk('public')->delete($curriculum->file_path);
        }

        $curriculum->delete();

        return response()->json(['message' => 'Curriculum deleted successfully']);
    }
}
