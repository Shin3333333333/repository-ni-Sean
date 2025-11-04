<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ProfessorController extends Controller
{
    public function updateDetails(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string',
            'specialization' => 'required|string',
            'type' => 'required|string',
            'time_unavailable' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $user->update(['name' => $request->name]);

        $user->professor()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'department' => $request->specialization,
                'type' => $request->type,
                'time_unavailable' => $request->time_unavailable,
                'status' => $request->status,
            ]
        );

        if ($user->is_temporary) {
            $user->update(['is_temporary' => 0]);
        }

        return response()->json(['message' => 'Professor details updated successfully.']);
    }

    public function getDetails(Request $request)
    {
        $user = Auth::user()->load('professor');

        if ($user->professor) {
            return response()->json([
                'name' => $user->name,
                'department' => $user->professor->department,
                'type' => $user->professor->type,
                'time_unavailable' => $user->professor->time_unavailable,
                'status' => $user->professor->status ?? 'Pending',
            ]);
        } else {
            return response()->json([
                'name' => $user->name,
                'department' => '',
                'type' => '',
                'time_unavailable' => '',
                'status' => 'Pending',
            ]);
        }
    }
}