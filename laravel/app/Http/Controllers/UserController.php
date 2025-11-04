<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return response()->json($user);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        try {
            // TODO: For better security, re-implement current_password check
            // if the frontend is updated to send it.
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user->password = Hash::make($request->password);
            $user->is_temporary = 0; // Set is_temporary to false
            $user->save();

            return response()->json(['message' => 'Password updated successfully.']);
        } catch (ValidationException $e) {
            Log::error('Password update validation failed: ' . $e->getMessage());
            return response()->json(['message' => 'Invalid data provided.'], 422);
        }
    }
}