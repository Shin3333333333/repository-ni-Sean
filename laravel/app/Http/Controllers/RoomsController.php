<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;

class RoomsController extends Controller
{
    public function index() {
        return response()->json(Room::all());
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'type' => 'required|string|max:255',
            'status' => 'required|string|in:Available,Unavailable',
        ]);

        $room = Room::create($request->only(['name','capacity','type','status']));
        return response()->json($room, 201);
    }
}
