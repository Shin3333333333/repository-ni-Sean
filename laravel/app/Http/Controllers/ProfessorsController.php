make <?php

namespace App\Http\Controllers;

use App\Mail\TemporaryAccountCreated;
use App\Models\Professor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ProfessorsController extends Controller
{
    public function index()
    {
        return response()->json(Professor::all());
    }

    /**
     * Normalize different shapes of time_unavailable into a single comma-separated string.
     * Accepts: string, array of strings, or array of objects with dayName/start/end.
     */
    protected function normalizeTimeUnavailable($tu): ?string
    {
        if (is_null($tu) || $tu === '') {
            return null;
        }

        // If it's already a string, trim and return
        if (is_string($tu)) {
            return trim($tu);
        }

        // If array, map appropriately
        if (is_array($tu)) {
            $parts = [];
            foreach ($tu as $item) {
                if (is_string($item)) {
                    $parts[] = trim($item);
                } elseif (is_array($item)) {
                    $day = $item['dayName'] ?? $item['day'] ?? null;
                    $start = $item['start'] ?? null;
                    $end = $item['end'] ?? null;
                    if ($day && $start && $end) {
                        $parts[] = trim($day) . ' ' . trim($start) . '–' . trim($end);
                    }
                }
            }
            return implode(', ', array_filter($parts, fn($p) => $p !== ''));
        }

        // Fallback: cast to string safely
        return (string) $tu;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'max_load' => 'required|integer|min:1',
            'status' => 'required|string',
            'time_unavailable' => 'nullable',
        ]);

        $data = $request->only(['name','type','department','max_load','status']);

        // Normalize time_unavailable to a single string to prevent "[object Object]"
        if ($request->has('time_unavailable')) {
            $tu = $request->input('time_unavailable');
            $data['time_unavailable'] = $this->normalizeTimeUnavailable($tu);
        }

        $professor = Professor::create($data);

        return response()->json([
                'data' => $professor,
                'message' => 'Professor created successfully'
            ], 201);
    }

    public function createTemporaryAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Check if a user with the given email already exists
        if (User::where('email', $request->email)->exists()) {
            return response()->json(['message' => 'A user with this email already exists.'], 409);
        }

        $password = Str::random(10);

        $user = User::create([
            'name' => 'Temporary Account',
            'email' => $request->email,
            'password' => Hash::make($password),
            'type_id' => 2,
        ]);

        Professor::create([
            'user_id' => $user->id,
            'name' => 'Temporary Faculty',
            'type' => 'Part-time',
            'department' => 'To be assigned',
            'max_load' => 0,
            'status' => 'Pending',
        ]);

        Mail::to($user->email)->send(new TemporaryAccountCreated($user, $password));

        return response()->json(['message' => 'Temporary account created successfully. An email has been sent with login details.']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'max_load' => 'required|integer|min:1',
            'status' => 'required|string',
            'time_unavailable' => 'nullable',
        ]);

        $professor = Professor::findOrFail($id);

        $data = $request->only(['name','type','department','max_load','status']);
        if ($request->has('time_unavailable')) {
            $tu = $request->input('time_unavailable');
            $data['time_unavailable'] = $this->normalizeTimeUnavailable($tu);
        }

        $professor->update($data);

        return response()->json(['data' => $professor]);
    }

    public function destroy($id)
    {
        $professor = Professor::findOrFail($id);
        $professor->delete();
        return response()->json(['message' => 'Professor deleted successfully']);
    }
}