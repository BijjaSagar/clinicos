<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\User;
use App\Models\ClinicRoom;
use App\Models\ClinicEquipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ClinicController extends Controller
{
    /**
     * Get current clinic details
     */
    public function show(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching clinic details', ['clinic_id' => $clinicId]);

        $clinic = Clinic::with(['locations', 'rooms', 'equipment'])
            ->findOrFail($clinicId);

        Log::info('Clinic details retrieved', ['clinic_id' => $clinicId]);

        return response()->json([
            'clinic' => $clinic,
        ]);
    }

    /**
     * Update clinic details
     */
    public function update(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Updating clinic', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'specialties' => 'sometimes|array',
            'gstin' => 'nullable|string|max:20',
            'pan' => 'nullable|string|max:12',
            'registration_number' => 'nullable|string|max:50',
            'address_line1' => 'nullable|string|max:200',
            'address_line2' => 'nullable|string|max:200',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'pincode' => 'nullable|string|max:6',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:150',
            'logo_url' => 'nullable|url|max:500',
            'settings' => 'nullable|array',
        ]);

        $clinic = Clinic::findOrFail($clinicId);
        $clinic->update($validated);

        Log::info('Clinic updated', ['clinic_id' => $clinicId, 'changes' => array_keys($validated)]);

        return response()->json([
            'message' => 'Clinic updated successfully',
            'clinic' => $clinic->fresh(),
        ]);
    }

    /**
     * Get all staff members
     */
    public function staff(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching staff list', ['clinic_id' => $clinicId]);

        $staff = User::forClinic($clinicId)
            ->whereNotIn('role', ['doctor'])
            ->active()
            ->get();

        Log::info('Staff list retrieved', ['clinic_id' => $clinicId, 'count' => $staff->count()]);

        return response()->json([
            'staff' => $staff,
        ]);
    }

    /**
     * Add new staff member
     */
    public function addStaff(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Adding new staff', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:15',
            'password' => 'required|string|min:8',
            'role' => 'required|in:receptionist,nurse,staff',
        ]);

        $user = User::create([
            'clinic_id' => $clinicId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        Log::info('Staff member added', ['user_id' => $user->id, 'role' => $user->role]);

        return response()->json([
            'message' => 'Staff member added successfully',
            'staff' => $user,
        ], 201);
    }

    /**
     * Remove staff member
     */
    public function removeStaff(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Removing staff', ['clinic_id' => $clinicId, 'user_id' => $id]);

        $user = User::forClinic($clinicId)->findOrFail($id);
        
        if ($user->role === 'owner') {
            Log::warning('Cannot remove owner', ['user_id' => $id]);
            return response()->json(['message' => 'Cannot remove clinic owner'], 403);
        }

        $user->update(['is_active' => false]);

        Log::info('Staff member removed', ['user_id' => $id]);

        return response()->json([
            'message' => 'Staff member removed successfully',
        ]);
    }

    /**
     * Get all doctors
     */
    public function doctors(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching doctors list', ['clinic_id' => $clinicId]);

        $doctors = User::forClinic($clinicId)
            ->doctors()
            ->active()
            ->get();

        Log::info('Doctors list retrieved', ['clinic_id' => $clinicId, 'count' => $doctors->count()]);

        return response()->json([
            'doctors' => $doctors,
        ]);
    }

    /**
     * Add new doctor
     */
    public function addDoctor(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Adding new doctor', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:15',
            'password' => 'required|string|min:8',
            'specialty' => 'required|string|max:50',
            'qualification' => 'nullable|string|max:200',
            'registration_number' => 'nullable|string|max:80',
            'hpr_id' => 'nullable|string|max:30',
        ]);

        $doctor = User::create([
            'clinic_id' => $clinicId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'doctor',
            'specialty' => $validated['specialty'],
            'qualification' => $validated['qualification'] ?? null,
            'registration_number' => $validated['registration_number'] ?? null,
            'hpr_id' => $validated['hpr_id'] ?? null,
            'is_active' => true,
        ]);

        Log::info('Doctor added', ['user_id' => $doctor->id, 'specialty' => $doctor->specialty]);

        return response()->json([
            'message' => 'Doctor added successfully',
            'doctor' => $doctor,
        ], 201);
    }

    /**
     * Get all rooms
     */
    public function rooms(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching rooms list', ['clinic_id' => $clinicId]);

        $rooms = ClinicRoom::where('clinic_id', $clinicId)
            ->active()
            ->get();

        Log::info('Rooms list retrieved', ['clinic_id' => $clinicId, 'count' => $rooms->count()]);

        return response()->json([
            'rooms' => $rooms,
        ]);
    }

    /**
     * Add new room
     */
    public function addRoom(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Adding new room', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'room_type' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'location_id' => 'nullable|exists:clinic_locations,id',
        ]);

        $room = ClinicRoom::create([
            'clinic_id' => $clinicId,
            'name' => $validated['name'],
            'room_type' => $validated['room_type'] ?? null,
            'capacity' => $validated['capacity'] ?? 1,
            'location_id' => $validated['location_id'] ?? null,
            'is_active' => true,
        ]);

        Log::info('Room added', ['room_id' => $room->id, 'name' => $room->name]);

        return response()->json([
            'message' => 'Room added successfully',
            'room' => $room,
        ], 201);
    }

    /**
     * Get all equipment
     */
    public function equipment(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching equipment list', ['clinic_id' => $clinicId]);

        $equipment = ClinicEquipment::where('clinic_id', $clinicId)
            ->active()
            ->get();

        Log::info('Equipment list retrieved', ['clinic_id' => $clinicId, 'count' => $equipment->count()]);

        return response()->json([
            'equipment' => $equipment,
        ]);
    }

    /**
     * Add new equipment
     */
    public function addEquipment(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Adding new equipment', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'equipment_type' => 'required|string|max:50',
            'serial_number' => 'nullable|string|max:100',
        ]);

        $equipment = ClinicEquipment::create([
            'clinic_id' => $clinicId,
            'name' => $validated['name'],
            'equipment_type' => $validated['equipment_type'],
            'serial_number' => $validated['serial_number'] ?? null,
            'is_active' => true,
        ]);

        Log::info('Equipment added', ['equipment_id' => $equipment->id, 'name' => $equipment->name]);

        return response()->json([
            'message' => 'Equipment added successfully',
            'equipment' => $equipment,
        ], 201);
    }
}
