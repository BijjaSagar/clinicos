<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        Log::info('AdminUserController@index');

        $query = User::with('clinic');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by clinic
        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->clinic_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->latest()->paginate(20);

        $stats = [
            'total' => User::count(),
            'super_admins' => User::where('role', 'super_admin')->count(),
            'owners' => User::where('role', 'owner')->count(),
            'doctors' => User::where('role', 'doctor')->count(),
            'staff' => User::whereIn('role', ['receptionist', 'nurse', 'staff'])->count(),
        ];

        $clinics = Clinic::select('id', 'name')->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'stats', 'clinics'));
    }

    public function create()
    {
        Log::info('AdminUserController@create');
        $clinics = Clinic::select('id', 'name')->orderBy('name')->get();
        return view('admin.users.create', compact('clinics'));
    }

    public function store(Request $request)
    {
        Log::info('AdminUserController@store', $request->except('password'));

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:15',
            'password' => 'required|string|min:8',
            'role' => 'required|in:super_admin,owner,doctor,receptionist,nurse,staff',
            'clinic_id' => 'nullable|exists:clinics,id',
            'specialty' => 'nullable|string|max:50',
        ]);

        // Super admins don't need a clinic
        if ($validated['role'] !== 'super_admin' && empty($validated['clinic_id'])) {
            return back()->withInput()->with('error', 'Non-admin users must be assigned to a clinic.');
        }

        try {
            $user = User::create([
                'clinic_id' => $validated['role'] === 'super_admin' ? null : $validated['clinic_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'specialty' => $validated['specialty'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            Log::info('User created by admin', ['user_id' => $user->id]);

            return redirect()
                ->route('admin.users.index')
                ->with('success', "User '{$user->name}' created successfully.");

        } catch (\Throwable $e) {
            Log::error('Admin user creation failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    public function edit(User $user)
    {
        Log::info('AdminUserController@edit', ['user_id' => $user->id]);
        $clinics = Clinic::select('id', 'name')->orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'clinics'));
    }

    public function update(Request $request, User $user)
    {
        Log::info('AdminUserController@update', ['user_id' => $user->id]);

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:15',
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:super_admin,owner,doctor,receptionist,nurse,staff',
            'clinic_id' => 'nullable|exists:clinics,id',
            'specialty' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        try {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'role' => $validated['role'],
                'clinic_id' => $validated['role'] === 'super_admin' ? null : $validated['clinic_id'],
                'specialty' => $validated['specialty'],
                'is_active' => $validated['is_active'] ?? true,
            ];

            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            Log::info('User updated by admin', ['user_id' => $user->id]);

            return redirect()
                ->route('admin.users.index')
                ->with('success', "User '{$user->name}' updated successfully.");

        } catch (\Throwable $e) {
            Log::error('Admin user update failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    public function toggleStatus(User $user)
    {
        Log::info('AdminUserController@toggleStatus', ['user_id' => $user->id]);

        // Don't allow deactivating yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        
        return back()->with('success', "User {$status} successfully.");
    }

    public function destroy(User $user)
    {
        Log::info('AdminUserController@destroy', ['user_id' => $user->id]);

        // Don't allow deleting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        try {
            $userName = $user->name;
            $user->delete(); // Soft delete

            Log::info('User deleted by admin', ['user_id' => $user->id]);

            return redirect()
                ->route('admin.users.index')
                ->with('success', "User '{$userName}' has been deleted.");

        } catch (\Throwable $e) {
            Log::error('Admin user delete failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }
}
