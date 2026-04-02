<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ClinicUserController extends Controller
{
    public function index()
    {
        Log::info('ClinicUserController@index', ['clinic_id' => auth()->user()->clinic_id]);

        $clinicId = auth()->user()->clinic_id;
        
        $users = User::where('clinic_id', $clinicId)
            ->orderByRaw("FIELD(role, 'owner', 'doctor', 'receptionist', 'nurse', 'staff')")
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => $users->count(),
            'doctors' => $users->where('role', 'doctor')->count(),
            'receptionists' => $users->where('role', 'receptionist')->count(),
            'nurses' => $users->where('role', 'nurse')->count(),
            'staff' => $users->where('role', 'staff')->count(),
            'active' => $users->where('is_active', true)->count(),
        ];

        return view('clinic-users.index', compact('users', 'stats'));
    }

    public function create()
    {
        Log::info('ClinicUserController@create');

        return view('clinic-users.create');
    }

    public function store(Request $request)
    {
        Log::info('ClinicUserController@store', ['data' => $request->except('password')]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'role' => ['required', Rule::in(['doctor', 'receptionist', 'nurse', 'staff', 'pharmacist', 'lab_technician'])],
            'specialty' => 'nullable|string|max:255',
            'consultation_fee' => 'nullable|numeric|min:0',
        ]);

        try {
            $clinicId = auth()->user()->clinic_id;

            $user = User::create([
                'clinic_id' => $clinicId,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'specialty' => $validated['specialty'] ?? null,
                'consultation_fee' => $validated['consultation_fee'] ?? 0,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            Log::info('User created for clinic', ['user_id' => $user->id, 'clinic_id' => $clinicId]);

            return redirect()->route('clinic.users.index')->with('success', 'User created successfully! They can now login.');

        } catch (\Throwable $e) {
            Log::error('Failed to create user', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    public function edit(User $user)
    {
        Log::info('ClinicUserController@edit', ['user_id' => $user->id]);

        // Ensure user belongs to the same clinic
        if ($user->clinic_id !== auth()->user()->clinic_id) {
            abort(403, 'You can only edit users from your clinic.');
        }

        return view('clinic-users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        Log::info('ClinicUserController@update', ['user_id' => $user->id]);

        // Ensure user belongs to the same clinic
        if ($user->clinic_id !== auth()->user()->clinic_id) {
            abort(403, 'You can only edit users from your clinic.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6|confirmed',
            'role' => ['required', Rule::in(['owner', 'doctor', 'receptionist', 'nurse', 'staff', 'pharmacist', 'lab_technician'])],
            'specialty' => 'nullable|string|max:255',
            'consultation_fee' => 'nullable|numeric|min:0',
        ]);

        try {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'role' => $validated['role'],
                'specialty' => $validated['specialty'] ?? null,
                'consultation_fee' => $validated['consultation_fee'] ?? $user->consultation_fee,
            ];

            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            Log::info('User updated', ['user_id' => $user->id]);

            return redirect()->route('clinic.users.index')->with('success', 'User updated successfully!');

        } catch (\Throwable $e) {
            Log::error('Failed to update user', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    public function toggleStatus(User $user)
    {
        Log::info('ClinicUserController@toggleStatus', ['user_id' => $user->id]);

        // Ensure user belongs to the same clinic
        if ($user->clinic_id !== auth()->user()->clinic_id) {
            abort(403, 'You can only manage users from your clinic.');
        }

        // Cannot deactivate yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        // Cannot deactivate owner
        if ($user->role === 'owner') {
            return back()->with('error', 'You cannot deactivate the clinic owner.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        Log::info("User {$status}", ['user_id' => $user->id]);

        return back()->with('success', "User {$status} successfully!");
    }

    public function destroy(User $user)
    {
        Log::info('ClinicUserController@destroy', ['user_id' => $user->id]);

        // Ensure user belongs to the same clinic
        if ($user->clinic_id !== auth()->user()->clinic_id) {
            abort(403, 'You can only delete users from your clinic.');
        }

        // Cannot delete yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Cannot delete owner
        if ($user->role === 'owner') {
            return back()->with('error', 'You cannot delete the clinic owner.');
        }

        try {
            $userName = $user->name;
            $user->delete();

            Log::info('User deleted', ['user_name' => $userName]);

            return redirect()->route('clinic.users.index')->with('success', "User '{$userName}' deleted successfully!");

        } catch (\Throwable $e) {
            Log::error('Failed to delete user', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }
}
