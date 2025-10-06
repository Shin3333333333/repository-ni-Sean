<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubjectsController extends Controller
{
    public function index()
    {
        // For now, just return something simple to verify it works
        return response()->json(['message' => 'SubjectsController working']);
    }
}
