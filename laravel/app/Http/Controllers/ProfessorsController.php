<?php

namespace App\Http\Controllers;

use App\Models\Professor;
use Illuminate\Http\Request;

class ProfessorsController extends Controller
{
    public function index()
    {
        return response()->json(Professor::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'max_load' => 'required|integer|min:1',
            'status' => 'required|string',
            'time_unavailable' => 'nullable|string',
        ]);

        $professor = Professor::create($request->only([
            'name','type','department','max_load','status','time_unavailable'
        ]));

        return response()->json($professor, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'max_load' => 'required|integer|min:1',
            'status' => 'required|string',
            'time_unavailable' => 'nullable|string',
        ]);

        $professor = Professor::findOrFail($id);
        $professor->update($request->only([
            'name','type','department','max_load','status','time_unavailable'
        ]));

        return response()->json($professor);
    }

    public function destroy($id)
    {
        $professor = Professor::findOrFail($id);
        $professor->delete();
        return response()->json(['message' => 'Professor deleted successfully']);
    }
}
