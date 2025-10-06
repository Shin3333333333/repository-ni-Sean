<?php

namespace App\Http\Controllers;

use App\Models\Professor;
use Illuminate\Http\Request;

class ProfessorsController extends Controller
{
    public function index()
    {
        return Professor::all();
    }

    public function store(Request $request)
    {
        $professor = Professor::create($request->all());
        return response()->json($professor, 201);
    }

    public function update(Request $request, $id)
    {
        $professor = Professor::findOrFail($id);
        $professor->update($request->all());
        return response()->json($professor);
    }

    public function destroy($id)
    {
        Professor::destroy($id);
        return response()->json(null, 204);
    }
}
