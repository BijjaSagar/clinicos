<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Multi-Location Controller
 * 
 * Manages multiple clinic locations for enterprise clinics:
 * - Add/edit locations
 * - Manage rooms per location
 * - Configure location-specific settings
 * - Location-based analytics
 */
class MultiLocationController extends Controller
{
    /**
     * Show locations management page
     */
    public function index(): View
    {
        Log::info('MultiLocationController: Loading locations');

        $clinicId = auth()->user()->clinic_id;

        if (!Schema::hasTable('clinic_locations')) {
            Log::warning('MultiLocationController: clinic_locations table missing', ['clinic_id' => $clinicId]);

            return view('settings.locations', [
                'locations' => collect(),
                'locationStats' => [],
                'locationsSchemaReady' => false,
            ]);
        }

        $locations = DB::table('clinic_locations')
            ->where('clinic_id', $clinicId)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();

        $usersHaveLocationIds = Schema::hasColumn('users', 'location_ids');
        Log::info('MultiLocationController: index schema', [
            'users_have_location_ids' => $usersHaveLocationIds,
        ]);

        $locationStats = [];
        foreach ($locations as $location) {
            $doctorCount = 0;
            if ($usersHaveLocationIds) {
                try {
                    $doctorCount = DB::table('users')
                        ->where('clinic_id', $clinicId)
                        ->whereJsonContains('location_ids', $location->id)
                        ->count();
                } catch (\Throwable $e) {
                    Log::warning('MultiLocationController: doctors count query failed', ['error' => $e->getMessage()]);
                }
            }

            $locationStats[$location->id] = [
                'rooms' => DB::table('clinic_rooms')
                    ->where('location_id', $location->id)
                    ->count(),
                'doctors' => $doctorCount,
                'appointments_today' => DB::table('appointments')
                    ->where('clinic_id', $clinicId)
                    ->where('location_id', $location->id)
                    ->whereDate('scheduled_at', today())
                    ->count(),
            ];
        }

        return view('settings.locations', compact('locations', 'locationStats') + ['locationsSchemaReady' => true]);
    }

    /**
     * Store a new location
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('MultiLocationController: Creating location');

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'address' => 'required|string|max:500',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:150',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|size:6',
            'operating_hours' => 'nullable|array',
            'is_primary' => 'nullable|boolean',
        ]);

        $clinicId = auth()->user()->clinic_id;

        try {
            if ($validated['is_primary'] ?? false) {
                DB::table('clinic_locations')
                    ->where('clinic_id', $clinicId)
                    ->update(['is_primary' => false]);
            }

            $locationId = DB::table('clinic_locations')->insertGetId([
                'clinic_id' => $clinicId,
                'name' => $validated['name'],
                'address' => $validated['address'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'city' => $validated['city'] ?? null,
                'pincode' => $validated['pincode'] ?? null,
                'operating_hours' => json_encode($validated['operating_hours'] ?? []),
                'is_primary' => $validated['is_primary'] ?? false,
                'is_active' => true,
                'created_at' => now(),
            ]);

            Log::info('MultiLocationController: Location created', ['location_id' => $locationId]);

            return response()->json([
                'success' => true,
                'location_id' => $locationId,
                'message' => 'Location added successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('MultiLocationController: Create error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a location
     */
    public function update(Request $request, int $locationId): JsonResponse
    {
        Log::info('MultiLocationController: Updating location', ['location_id' => $locationId]);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'address' => 'sometimes|string|max:500',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:150',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|size:6',
            'operating_hours' => 'nullable|array',
            'is_primary' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $clinicId = auth()->user()->clinic_id;

        try {
            $location = DB::table('clinic_locations')
                ->where('id', $locationId)
                ->where('clinic_id', $clinicId)
                ->first();

            if (!$location) {
                return response()->json(['success' => false, 'error' => 'Location not found'], 404);
            }

            if (isset($validated['is_primary']) && $validated['is_primary']) {
                DB::table('clinic_locations')
                    ->where('clinic_id', $clinicId)
                    ->update(['is_primary' => false]);
            }

            $updateData = array_filter([
                'name' => $validated['name'] ?? null,
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'city' => $validated['city'] ?? null,
                'pincode' => $validated['pincode'] ?? null,
                'is_primary' => $validated['is_primary'] ?? null,
                'is_active' => $validated['is_active'] ?? null,
            ], fn($v) => $v !== null);

            if (isset($validated['operating_hours'])) {
                $updateData['operating_hours'] = json_encode($validated['operating_hours']);
            }

            DB::table('clinic_locations')
                ->where('id', $locationId)
                ->update($updateData);

            Log::info('MultiLocationController: Location updated', ['location_id' => $locationId]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('MultiLocationController: Update error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a location
     */
    public function destroy(int $locationId): JsonResponse
    {
        Log::info('MultiLocationController: Deleting location', ['location_id' => $locationId]);

        $clinicId = auth()->user()->clinic_id;

        try {
            $location = DB::table('clinic_locations')
                ->where('id', $locationId)
                ->where('clinic_id', $clinicId)
                ->first();

            if (!$location) {
                return response()->json(['success' => false, 'error' => 'Location not found'], 404);
            }

            if ($location->is_primary) {
                return response()->json(['success' => false, 'error' => 'Cannot delete primary location'], 400);
            }

            DB::table('clinic_locations')
                ->where('id', $locationId)
                ->delete();

            Log::info('MultiLocationController: Location deleted', ['location_id' => $locationId]);

            return response()->json([
                'success' => true,
                'message' => 'Location deleted',
            ]);
        } catch (\Throwable $e) {
            Log::error('MultiLocationController: Delete error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get rooms for a location
     */
    public function getRooms(int $locationId): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $rooms = DB::table('clinic_rooms')
            ->where('clinic_id', $clinicId)
            ->where('location_id', $locationId)
            ->orderBy('name')
            ->get();

        Log::info('MultiLocationController: Rooms retrieved', [
            'location_id' => $locationId,
            'room_count' => $rooms->count(),
        ]);

        return response()->json([
            'success' => true,
            'rooms' => $rooms,
        ]);
    }

    /**
     * Get location-wise analytics for dashboard
     */
    public function analytics(): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('MultiLocationController: Location analytics requested', ['clinic_id' => $clinicId]);

        $locations = DB::table('clinic_locations')
            ->where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->get();

        $invoicesHaveLocation = Schema::hasColumn('invoices', 'location_id');
        $usersHaveLocationIds = Schema::hasColumn('users', 'location_ids');

        $analytics = $locations->map(function ($location) use ($clinicId, $invoicesHaveLocation, $usersHaveLocationIds) {
            $todayAppts = DB::table('appointments')
                ->where('clinic_id', $clinicId)
                ->where('location_id', $location->id)
                ->whereDate('scheduled_at', today())
                ->count();

            $monthRevenue = 0.0;
            if ($invoicesHaveLocation) {
                try {
                    $monthRevenue = (float) (DB::table('invoices')
                        ->where('clinic_id', $clinicId)
                        ->where('location_id', $location->id)
                        ->whereMonth('invoice_date', now()->month)
                        ->whereYear('invoice_date', now()->year)
                        ->sum('total') ?? 0);
                } catch (\Throwable $e) {
                    Log::warning('MultiLocationController: monthRevenue query failed', ['error' => $e->getMessage()]);
                }
            }

            $monthPatients = DB::table('appointments')
                ->where('clinic_id', $clinicId)
                ->where('location_id', $location->id)
                ->whereMonth('scheduled_at', now()->month)
                ->distinct('patient_id')
                ->count('patient_id');

            $doctors = 0;
            if ($usersHaveLocationIds) {
                try {
                    $doctors = DB::table('users')
                        ->where('clinic_id', $clinicId)
                        ->whereIn('role', ['doctor', 'owner'])
                        ->where('is_active', true)
                        ->where(function ($q) use ($location) {
                            $q->whereJsonContains('location_ids', $location->id)
                                ->orWhereNull('location_ids');
                        })
                        ->count();
                } catch (\Throwable $e) {
                    Log::warning('MultiLocationController: analytics doctors query failed', ['error' => $e->getMessage()]);
                }
            } else {
                try {
                    $doctors = DB::table('users')
                        ->where('clinic_id', $clinicId)
                        ->whereIn('role', ['doctor', 'owner'])
                        ->where('is_active', true)
                        ->count();
                } catch (\Throwable $e) {
                    Log::warning('MultiLocationController: analytics doctors fallback failed', ['error' => $e->getMessage()]);
                }
            }

            Log::info('MultiLocationController: Location analytics computed', [
                'location_id' => $location->id,
                'today_appts' => $todayAppts,
                'month_revenue' => $monthRevenue,
                'month_patients' => $monthPatients,
                'doctors' => $doctors,
            ]);

            return [
                'id' => $location->id,
                'name' => $location->name,
                'city' => $location->city,
                'is_primary' => (bool) $location->is_primary,
                'today_appointments' => $todayAppts,
                'month_revenue' => round((float) $monthRevenue, 2),
                'month_unique_patients' => $monthPatients,
                'active_doctors' => $doctors,
            ];
        });

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
        ]);
    }
}
