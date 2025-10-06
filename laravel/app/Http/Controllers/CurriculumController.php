<?php
use Illuminate\Http\Request;
use App\Models\Curriculum;
use App\Models\Subject;

class CurriculumController extends Controller
{
    public function upload(Request $request)
    {
        $file = $request->file('file');
        $filename = time().'_'.$file->getClientOriginalName();
        $path = $file->storeAs('curriculums', $filename, 'public');

        $curriculum = Curriculum::create([
            'name' => $filename,
            'file_path' => $path
        ]);

        // Optionally parse the file to extract subjects automatically

        return response()->json(['curriculum_id' => $curriculum->id]);
    }

    public function show($id)
    {
        $curriculum = Curriculum::findOrFail($id);
        $subjects = Subject::where('curriculum_id', $id)->get();

        return response()->json([
            'curriculum' => $curriculum,
            'subjects' => $subjects
        ]);
    }
}
?>