<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\User;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminClinicController extends Controller
{
    public function index(Request $request)
    {
        Log::info('AdminClinicController@index');

        $query = Clinic::with(['owner', 'users'])
            ->withCount(['patients', 'appointments', 'invoices']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Filter by plan
        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'trial') {
                $query->whereNotNull('trial_ends_at')
                      ->where('trial_ends_at', '>', now());
            } elseif ($request->status === 'expired') {
                $query->whereNotNull('trial_ends_at')
                      ->where('trial_ends_at', '<=', now());
            }
        }

        $clinics = $query->latest()->paginate(20);

        $stats = [
            'total' => Clinic::count(),
            'active' => Clinic::where('is_active', true)->count(),
            'trial' => Clinic::whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now())->count(),
            'paid' => Clinic::whereIn('plan', ['solo', 'small', 'group', 'enterprise'])->where('is_active', true)->count(),
        ];

        return view('admin.clinics.index', compact('clinics', 'stats'));
    }

    public function create()
    {
        Log::info('AdminClinicController@create');
        return view('admin.clinics.create');
    }

    public function store(Request $request)
    {
        Log::info('AdminClinicController@store', $request->all());

        $validated = $request->validate([
            'clinic_name' => 'required|string|max:200',
            'owner_name' => 'required|string|max:200',
            'owner_email' => 'required|email|unique:users,email',
            'owner_phone' => 'required|string|max:15',
            'owner_password' => 'required|string|min:8',
            'plan' => 'required|in:trial,solo,small,group,enterprise',
            'specialty' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'trial_days' => 'nullable|integer|min:0|max:365',
        ]);

        try {
            // Create clinic
            $clinic = Clinic::create([
                'name' => $validated['clinic_name'],
                'slug' => Str::slug($validated['clinic_name']) . '-' . Str::random(6),
                'plan' => $validated['plan'],
                'specialties' => $validated['specialty'] ? [$validated['specialty']] : ['general'],
                'city' => $validated['city'] ?? 'Unknown',
                'state' => $validated['state'] ?? 'Unknown',
                'is_active' => true,
                'trial_ends_at' => $validated['plan'] === 'trial' 
                    ? now()->addDays($validated['trial_days'] ?? 30) 
                    : null,
            ]);

            // Create owner user
            $owner = User::create([
                'clinic_id' => $clinic->id,
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'phone' => $validated['owner_phone'],
                'password' => Hash::make($validated['owner_password']),
                'role' => 'owner',
                'is_active' => true,
            ]);

            // Link owner to clinic
            $clinic->update(['owner_user_id' => $owner->id]);

            Log::info('Clinic created by admin', ['clinic_id' => $clinic->id, 'owner_id' => $owner->id]);

            return redirect()
                ->route('admin.clinics.show', $clinic)
                ->with('success', "Clinic '{$clinic->name}' created successfully. Login credentials sent to {$owner->email}.");

        } catch (\Throwable $e) {
            Log::error('Admin clinic creation failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to create clinic: ' . $e->getMessage());
        }
    }

    public function show(Clinic $clinic)
    {
        Log::info('AdminClinicController@show', ['clinic_id' => $clinic->id]);

        $clinic->load(['owner', 'users', 'locations']);

        // Get clinic statistics
        $stats = [
            'total_patients' => Patient::where('clinic_id', $clinic->id)->count(),
            'total_appointments' => Appointment::where('clinic_id', $clinic->id)->count(),
            'total_invoices' => Invoice::where('clinic_id', $clinic->id)->count(),
            'total_revenue' => Invoice::where('clinic_id', $clinic->id)
                ->where('payment_status', 'paid')
                ->sum('total') ?? 0,
            'this_month_revenue' => Invoice::where('clinic_id', $clinic->id)
                ->where('payment_status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->sum('total') ?? 0,
            'staff_count' => User::where('clinic_id', $clinic->id)->count(),
        ];

        // Recent activity
        $recentPatients = Patient::where('clinic_id', $clinic->id)
            ->latest()
            ->limit(5)
            ->get();

        $recentInvoices = Invoice::where('clinic_id', $clinic->id)
            ->with('patient')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.clinics.show', compact('clinic', 'stats', 'recentPatients', 'recentInvoices'));
    }

    public function edit(Clinic $clinic)
    {
        Log::info('AdminClinicController@edit', ['clinic_id' => $clinic->id]);
        $clinic->load('owner');
        return view('admin.clinics.edit', compact('clinic'));
    }

    public function update(Request $request, Clinic $clinic)
    {
        Log::info('AdminClinicController@update', ['clinic_id' => $clinic->id, 'data' => $request->all()]);

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'plan' => 'required|in:trial,solo,small,group,enterprise',
            'specialty' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'gstin' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'trial_ends_at' => 'nullable|date',
        ]);

        try {
            $clinic->update([
                'name' => $validated['name'],
                'plan' => $validated['plan'],
                'specialties' => $validated['specialty'] ? [$validated['specialty']] : $clinic->specialties,
                'city' => $validated['city'] ?? $clinic->city,
                'state' => $validated['state'] ?? $clinic->state,
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'gstin' => $validated['gstin'],
                'is_active' => $validated['is_active'] ?? true,
                'trial_ends_at' => $validated['trial_ends_at'] ?? $clinic->trial_ends_at,
            ]);

            Log::info('Clinic updated by admin', ['clinic_id' => $clinic->id]);

            return redirect()
                ->route('admin.clinics.show', $clinic)
                ->with('success', 'Clinic updated successfully.');

        } catch (\Throwable $e) {
            Log::error('Admin clinic update failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update clinic: ' . $e->getMessage());
        }
    }

    public function toggleStatus(Clinic $clinic)
    {
        Log::info('AdminClinicController@toggleStatus', ['clinic_id' => $clinic->id]);

        $clinic->update(['is_active' => !$clinic->is_active]);

        $status = $clinic->is_active ? 'activated' : 'deactivated';
        
        return back()->with('success', "Clinic {$status} successfully.");
    }

    public function extendTrial(Request $request, Clinic $clinic)
    {
        Log::info('AdminClinicController@extendTrial', ['clinic_id' => $clinic->id]);

        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $currentEnd = $clinic->trial_ends_at ?? now();
        $newEnd = $currentEnd->addDays($validated['days']);
        
        $clinic->update(['trial_ends_at' => $newEnd]);

        return back()->with('success', "Trial extended by {$validated['days']} days. New end date: {$newEnd->format('d M Y')}");
    }

    public function destroy(Clinic $clinic)
    {
        Log::info('AdminClinicController@destroy', ['clinic_id' => $clinic->id]);

        try {
            $clinicName = $clinic->name;
            $clinic->delete(); // Soft delete

            Log::info('Clinic deleted by admin', ['clinic_id' => $clinic->id]);

            return redirect()
                ->route('admin.clinics.index')
                ->with('success', "Clinic '{$clinicName}' has been deleted.");

        } catch (\Throwable $e) {
            Log::error('Admin clinic delete failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to delete clinic: ' . $e->getMessage());
        }
    }

    public function impersonate(Clinic $clinic)
    {
        Log::info('AdminClinicController@impersonate', ['clinic_id' => $clinic->id, 'admin_id' => auth()->id()]);

        $owner = $clinic->owner;
        
        if (!$owner) {
            return back()->with('error', 'Clinic has no owner to impersonate.');
        }

        // Store admin ID in session for returning later
        session(['impersonating_from' => auth()->id()]);
        
        // Login as clinic owner
        auth()->login($owner);

        Log::info('Admin impersonating clinic owner', ['admin_id' => session('impersonating_from'), 'owner_id' => $owner->id]);

        return redirect()->route('dashboard')->with('info', "You are now viewing as {$owner->name} ({$clinic->name}). Click 'Return to Admin' to exit.");
    }
}
