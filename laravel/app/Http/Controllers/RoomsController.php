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
    public function destroy($id)
{
    $room = Room::find($id);

    if (!$room) {
        return response()->json(['error' => 'Room not found'], 404);
    }

    try {
        $room->delete();
        return response()->json(['message' => 'Room deleted successfully']);
    } catch (\Exception $e) {
        // This will catch foreign key constraint errors or other DB errors
        return response()->json([
            'error' => 'Failed to delete room',
            'details' => $e->getMessage()
        ], 500);
    }
}
public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'capacity' => 'required|integer|min:1',
        'type' => 'required|string|max:255',
        'status' => 'required|string|in:Available,Unavailable',
    ]);

    $room = Room::findOrFail($id); // find the room or throw 404
    $room->update($request->only(['name', 'capacity', 'type', 'status']));

    return response()->json($room);
}


}
