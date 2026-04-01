<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\WearableReading;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class WearableWebController extends Controller
{
    public function index(Request $request): View
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('WearableWebController@index', ['clinic_id' => $clinicId]);

        $patients = Patient::where('clinic_id', $clinicId)->orderBy('name')->get(['id', 'name']);
        $wearablesSchemaReady = Schema::hasTable('wearable_readings');

        $readings = new LengthAwarePaginator([], 0, 40, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        try {
            if ($wearablesSchemaReady) {
                $q = WearableReading::with('patient')
                    ->where('clinic_id', $clinicId)
                    ->orderByDesc('recorded_at');

                if ($request->filled('patient_id')) {
                    $q->where('patient_id', $request->patient_id);
                }

                $readings = $q->paginate(40);
            } else {
                Log::warning('WearableWebController: wearable_readings missing — run migrations', [
                    'clinic_id' => $clinicId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('WearableWebController@index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return view('wearables.index', compact('readings', 'patients', 'wearablesSchemaReady'));
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('WearableWebController@importCsv', ['clinic_id' => $clinicId]);

        if (!Schema::hasTable('wearable_readings')) {
            Log::warning('WearableWebController@importCsv: table missing');

            return back()->with('error', 'Wearable readings table missing. Run php artisan migrate.');
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'device_type' => 'required|string|max:64',
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);
        abort_unless($patient->clinic_id === $clinicId, 403);

        $path = $request->file('file')->getPathname();
        Log::info('WearableWebController: CSV path', ['path' => $path]);
        $fh = fopen($path, 'r');
        if ($fh === false) {
            Log::error('WearableWebController: could not open CSV');

            return back()->with('error', 'Could not read file.');
        }

        $header = fgetcsv($fh);
        Log::info('WearableWebController: CSV header', ['header' => $header]);

        $count = 0;
        while (($row = fgetcsv($fh)) !== false) {
            if (count($row) < 2) {
                continue;
            }
            $map = [];
            foreach ($header ?: [] as $i => $key) {
                $map[strtolower(trim((string) $key))] = $row[$i] ?? null;
            }

            $recordedAt = $map['recorded_at'] ?? $map['date'] ?? $map['time'] ?? null;
            try {
                $ts = $recordedAt ? \Carbon\Carbon::parse($recordedAt) : now();
            } catch (\Throwable $e) {
                $ts = now();
            }

            WearableReading::create([
                'clinic_id' => $clinicId,
                'patient_id' => $patient->id,
                'device_type' => $validated['device_type'],
                'source' => 'csv_import',
                'recorded_at' => $ts,
                'systolic' => isset($map['systolic']) ? (int) $map['systolic'] : (isset($map['sbp']) ? (int) $map['sbp'] : null),
                'diastolic' => isset($map['diastolic']) ? (int) $map['diastolic'] : (isset($map['dbp']) ? (int) $map['dbp'] : null),
                'heart_rate' => isset($map['heart_rate']) ? (int) $map['heart_rate'] : (isset($map['hr']) ? (int) $map['hr'] : null),
                'glucose_mg_dl' => isset($map['glucose']) ? (int) $map['glucose'] : (isset($map['sugar']) ? (int) $map['sugar'] : null),
                'raw' => $map,
            ]);
            $count++;
        }
        fclose($fh);

        Log::info('WearableWebController: import complete', ['rows' => $count]);

        return back()->with('success', "Imported {$count} reading(s).");
    }
}
