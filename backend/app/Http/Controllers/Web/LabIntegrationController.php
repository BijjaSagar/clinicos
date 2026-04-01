<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Lab Integration Controller
 * 
 * Handles integration with major Indian diagnostic labs:
 * - Dr. Lal PathLabs
 * - SRL Diagnostics
 * - Thyrocare
 * - Metropolis
 * - Pathkind Labs
 */
class LabIntegrationController extends Controller
{
    private ?WhatsAppService $whatsAppService;

    public function __construct(?WhatsAppService $whatsAppService = null)
    {
        $this->whatsAppService = $whatsAppService;
    }

    private array $labProviders = [
        'lal_pathlabs' => [
            'name' => 'Dr. Lal PathLabs',
            'api_base' => 'https://api.lalpathlabs.com/v1',
            'code' => 'LALPL',
        ],
        'srl' => [
            'name' => 'SRL Diagnostics',
            'api_base' => 'https://api.srl.in/v1',
            'code' => 'SRL',
        ],
        'thyrocare' => [
            'name' => 'Thyrocare',
            'api_base' => 'https://api.thyrocare.com/v3',
            'code' => 'THYRO',
        ],
        'metropolis' => [
            'name' => 'Metropolis',
            'api_base' => 'https://api.metropolisindia.com/v1',
            'code' => 'METRO',
        ],
        'pathkind' => [
            'name' => 'Pathkind Labs',
            'api_base' => 'https://api.pathkindlabs.com/v1',
            'code' => 'PATHK',
        ],
    ];

    /**
     * Show lab integration dashboard
     */
    public function index(): View
    {
        Log::info('LabIntegrationController: Loading dashboard');

        $clinicId = auth()->user()->clinic_id;

        $stats = [
            'orders_today' => 0,
            'pending_results' => 0,
            'results_received' => 0,
        ];
        $recentOrders = collect();
        $labSchemaReady = Schema::hasTable('lab_orders');

        try {
            if ($labSchemaReady) {
                $stats = [
                    'orders_today' => \DB::table('lab_orders')
                        ->where('clinic_id', $clinicId)
                        ->whereDate('created_at', today())
                        ->count(),
                    'pending_results' => \DB::table('lab_orders')
                        ->where('clinic_id', $clinicId)
                        ->where('status', 'processing')
                        ->count(),
                    'results_received' => \DB::table('lab_orders')
                        ->where('clinic_id', $clinicId)
                        ->where('status', 'completed')
                        ->whereMonth('created_at', now()->month)
                        ->count(),
                ];

                $recentOrders = \DB::table('lab_orders')
                    ->where('lab_orders.clinic_id', $clinicId)
                    ->join('patients', 'lab_orders.patient_id', '=', 'patients.id')
                    ->select('lab_orders.*', 'patients.name as patient_name')
                    ->orderByDesc('lab_orders.created_at')
                    ->limit(20)
                    ->get();
            } else {
                Log::warning('LabIntegrationController: lab_orders table missing — run migrations', [
                    'clinic_id' => $clinicId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('LabIntegrationController: index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return view('lab.index', compact('stats', 'recentOrders', 'labSchemaReady'));
    }

    /**
     * Get test catalog from a lab
     */
    public function getTestCatalog(Request $request, string $provider): JsonResponse
    {
        Log::info('LabIntegrationController: Getting test catalog', ['provider' => $provider]);

        if (!isset($this->labProviders[$provider])) {
            return response()->json(['success' => false, 'error' => 'Invalid provider'], 400);
        }

        $search = $request->input('search', '');

        try {
            $tests = $this->fetchProviderCatalog($provider, $search);

            return response()->json([
                'success' => true,
                'provider' => $this->labProviders[$provider]['name'],
                'tests' => $tests,
            ]);
        } catch (\Throwable $e) {
            Log::error('LabIntegrationController: Catalog error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a lab order
     */
    public function createOrder(Request $request): JsonResponse
    {
        Log::info('LabIntegrationController: Creating order');

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'provider' => 'required|string',
            'tests' => 'required|array|min:1',
            'tests.*.code' => 'required|string',
            'tests.*.name' => 'required|string',
            'tests.*.price' => 'required|numeric',
            'sample_collection_type' => 'required|in:home,lab',
            'collection_date' => 'nullable|date',
            'collection_address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        $clinicId = auth()->user()->clinic_id;
        $patient = Patient::find($validated['patient_id']);

        if ($patient->clinic_id !== $clinicId) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        try {
            $orderNumber = 'LAB' . $clinicId . date('ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $totalAmount = array_sum(array_column($validated['tests'], 'price'));

            $orderId = \DB::table('lab_orders')->insertGetId([
                'clinic_id' => $clinicId,
                'patient_id' => $validated['patient_id'],
                'visit_id' => $validated['visit_id'],
                'order_number' => $orderNumber,
                'provider' => $validated['provider'],
                'provider_name' => $this->labProviders[$validated['provider']]['name'] ?? $validated['provider'],
                'tests' => json_encode($validated['tests']),
                'total_amount' => $totalAmount,
                'sample_collection_type' => $validated['sample_collection_type'],
                'collection_date' => $validated['collection_date'],
                'collection_address' => $validated['collection_address'],
                'notes' => $validated['notes'],
                'status' => 'pending',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $externalOrderId = $this->submitToProvider(
                $validated['provider'],
                $orderNumber,
                $validated,
                $clinicId
            );
            if (!empty($externalOrderId)) {
                \DB::table('lab_orders')
                    ->where('id', $orderId)
                    ->update([
                        'external_order_id' => $externalOrderId,
                        'updated_at' => now(),
                    ]);
            }

            Log::info('LabIntegrationController: Order created', [
                'order_id' => $orderId,
                'provider' => $validated['provider'],
                'external_order_id' => $externalOrderId,
            ]);

            return response()->json([
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'external_order_id' => $externalOrderId,
                'message' => 'Lab order created successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('LabIntegrationController: Order error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get order status
     */
    public function getOrderStatus(int $orderId): JsonResponse
    {
        Log::info('LabIntegrationController: Getting order status', ['order_id' => $orderId]);

        $clinicId = auth()->user()->clinic_id;

        $order = \DB::table('lab_orders')
            ->where('id', $orderId)
            ->where('clinic_id', $clinicId)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'error' => 'Order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'provider' => $order->provider_name,
                'tests' => json_decode($order->tests, true),
                'total_amount' => $order->total_amount,
                'result_url' => $order->result_url,
                'created_at' => $order->created_at,
            ],
        ]);
    }

    /**
     * Download lab result
     */
    public function downloadResult(int $orderId)
    {
        Log::info('LabIntegrationController: Downloading result', ['order_id' => $orderId]);

        $clinicId = auth()->user()->clinic_id;

        $order = \DB::table('lab_orders')
            ->where('id', $orderId)
            ->where('clinic_id', $clinicId)
            ->first();

        if (!$order || !$order->result_url) {
            abort(404, 'Result not found');
        }

        // In production, fetch from storage or lab API
        return redirect($order->result_url);
    }

    /**
     * Handle webhook from lab provider
     */
    public function handleWebhook(Request $request, string $provider): JsonResponse
    {
        Log::info('LabIntegrationController: Webhook received', ['provider' => $provider, 'data' => $request->all()]);

        // Verify webhook signature (provider-specific)
        
        $orderNumber = $request->input('order_id') ?? $request->input('order_number');
        $status = $request->input('status');
        $resultUrl = $request->input('result_url');

        if ($orderNumber) {
            $existingOrder = \DB::table('lab_orders')
                ->where('order_number', $orderNumber)
                ->first();

            Log::info('LabIntegrationController: Existing order resolved for webhook', [
                'provider' => $provider,
                'order_number' => $orderNumber,
                'order_exists' => !empty($existingOrder),
                'previous_status' => $existingOrder->status ?? null,
                'previous_result_url_present' => !empty($existingOrder->result_url ?? null),
            ]);

            $updateData = ['updated_at' => now()];

            if ($status) {
                $statusMap = [
                    'sample_collected' => 'processing',
                    'processing' => 'processing',
                    'completed' => 'completed',
                    'cancelled' => 'cancelled',
                ];
                $updateData['status'] = $statusMap[$status] ?? $status;
                Log::info('LabIntegrationController: Webhook status mapped', [
                    'incoming_status' => $status,
                    'mapped_status' => $updateData['status'],
                    'order_number' => $orderNumber,
                ]);
            }

            if ($resultUrl) {
                $updateData['result_url'] = $resultUrl;
                $updateData['result_received_at'] = now();
                Log::info('LabIntegrationController: Webhook carried result URL', [
                    'order_number' => $orderNumber,
                    'result_url' => $resultUrl,
                ]);
            }

            \DB::table('lab_orders')
                ->where('order_number', $orderNumber)
                ->update($updateData);

            Log::info('LabIntegrationController: Order updated via webhook', ['order_number' => $orderNumber]);

            $becameCompleted = ($existingOrder->status ?? null) !== 'completed' && (($updateData['status'] ?? null) === 'completed');
            $resultJustArrived = empty($existingOrder->result_url ?? null) && !empty($resultUrl);

            if ($this->whatsAppService && ($becameCompleted || $resultJustArrived)) {
                Log::info('LabIntegrationController: Evaluating WhatsApp lab-result trigger', [
                    'order_number' => $orderNumber,
                    'became_completed' => $becameCompleted,
                    'result_just_arrived' => $resultJustArrived,
                ]);

                try {
                    $labOrder = LabOrder::where('order_number', $orderNumber)->first();
                    $patient = $labOrder ? Patient::find($labOrder->patient_id) : null;

                    if ($labOrder && $patient && !empty($patient->phone)) {
                        $response = $this->whatsAppService->sendLabResults($patient, $labOrder);
                        Log::info('LabIntegrationController: Lab result WhatsApp trigger sent', [
                            'order_number' => $orderNumber,
                            'patient_id' => $patient->id,
                            'whatsapp_success' => $response['success'] ?? null,
                        ]);
                    } else {
                        Log::warning('LabIntegrationController: Skipped lab result WhatsApp trigger due to missing entities', [
                            'order_number' => $orderNumber,
                            'has_lab_order' => !empty($labOrder),
                            'has_patient' => !empty($patient),
                            'has_patient_phone' => !empty($patient?->phone),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('LabIntegrationController: Lab result WhatsApp trigger failed', [
                        'order_number' => $orderNumber,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::info('LabIntegrationController: WhatsApp lab-result trigger conditions not met', [
                    'order_number' => $orderNumber,
                    'has_whatsapp_service' => !empty($this->whatsAppService),
                    'became_completed' => $becameCompleted,
                    'result_just_arrived' => $resultJustArrived,
                ]);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Sync with Thyrocare API
     */
    public function syncThyrocare(Request $request): JsonResponse
    {
        Log::info('LabIntegrationController: Syncing Thyrocare');

        $apiKey = config('services.labs.thyrocare_api_key');

        if (!$apiKey) {
            return response()->json(['success' => false, 'error' => 'Thyrocare API not configured'], 503);
        }

        try {
            // Get product master
            $response = Http::withHeaders([
                'Api-Key' => $apiKey,
            ])->get('https://www.thyrocare.com/API/PRODUCT-MASTER.aspx', [
                'API_KEY' => $apiKey,
            ]);

            if ($response->successful()) {
                $products = $response->json();

                return response()->json([
                    'success' => true,
                    'products' => $products,
                ]);
            }

            return response()->json(['success' => false, 'error' => 'Sync failed'], 500);
        } catch (\Throwable $e) {
            Log::error('LabIntegrationController: Thyrocare sync error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get sample tests for demo
     */
    private function getSampleTests(string $provider, string $search): array
    {
        $allTests = [
            ['code' => 'CBC', 'name' => 'Complete Blood Count', 'price' => 350, 'category' => 'Hematology'],
            ['code' => 'LFT', 'name' => 'Liver Function Test', 'price' => 650, 'category' => 'Biochemistry'],
            ['code' => 'KFT', 'name' => 'Kidney Function Test', 'price' => 550, 'category' => 'Biochemistry'],
            ['code' => 'LIPID', 'name' => 'Lipid Profile', 'price' => 450, 'category' => 'Biochemistry'],
            ['code' => 'TSH', 'name' => 'Thyroid Stimulating Hormone', 'price' => 300, 'category' => 'Hormone'],
            ['code' => 'T3T4', 'name' => 'T3, T4, TSH Panel', 'price' => 650, 'category' => 'Hormone'],
            ['code' => 'HBA1C', 'name' => 'Glycated Hemoglobin', 'price' => 450, 'category' => 'Diabetes'],
            ['code' => 'FBS', 'name' => 'Fasting Blood Sugar', 'price' => 80, 'category' => 'Diabetes'],
            ['code' => 'PPBS', 'name' => 'Post Prandial Blood Sugar', 'price' => 80, 'category' => 'Diabetes'],
            ['code' => 'UREA', 'name' => 'Blood Urea', 'price' => 120, 'category' => 'Biochemistry'],
            ['code' => 'CREAT', 'name' => 'Serum Creatinine', 'price' => 120, 'category' => 'Biochemistry'],
            ['code' => 'URIC', 'name' => 'Uric Acid', 'price' => 150, 'category' => 'Biochemistry'],
            ['code' => 'VITD', 'name' => 'Vitamin D (25-OH)', 'price' => 1200, 'category' => 'Vitamins'],
            ['code' => 'VITB12', 'name' => 'Vitamin B12', 'price' => 900, 'category' => 'Vitamins'],
            ['code' => 'IRON', 'name' => 'Iron Studies', 'price' => 750, 'category' => 'Hematology'],
            ['code' => 'CRP', 'name' => 'C-Reactive Protein', 'price' => 550, 'category' => 'Inflammation'],
            ['code' => 'ESR', 'name' => 'Erythrocyte Sedimentation Rate', 'price' => 100, 'category' => 'Hematology'],
            ['code' => 'URINE', 'name' => 'Urine Routine & Microscopy', 'price' => 100, 'category' => 'Urine'],
            ['code' => 'STOOL', 'name' => 'Stool Routine', 'price' => 150, 'category' => 'Stool'],
            ['code' => 'ECG', 'name' => 'Electrocardiogram', 'price' => 250, 'category' => 'Cardiac'],
        ];

        if ($search) {
            $allTests = array_filter($allTests, function ($test) use ($search) {
                return stripos($test['name'], $search) !== false || stripos($test['code'], $search) !== false;
            });
        }

        return array_values($allTests);
    }

    private function fetchProviderCatalog(string $provider, string $search): array
    {
        $providerConfig = config("services.labs.{$provider}", []);
        $apiKey = (string) ($providerConfig['api_key'] ?? '');
        $apiBase = (string) ($providerConfig['api_base'] ?? ($this->labProviders[$provider]['api_base'] ?? ''));

        Log::info('LabIntegrationController: Fetch provider catalog attempt', [
            'provider' => $provider,
            'api_base' => $apiBase,
            'has_api_key' => !empty($apiKey),
            'search' => $search,
        ]);

        if (empty($apiKey) || empty($apiBase)) {
            Log::warning('LabIntegrationController: Provider API not configured, using sample catalog', [
                'provider' => $provider,
            ]);
            return $this->getSampleTests($provider, $search);
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                ])
                ->get(rtrim($apiBase, '/') . '/tests', [
                    'search' => $search,
                    'limit' => 50,
                ]);

            if ($response->successful()) {
                $raw = $response->json('tests') ?? $response->json('data') ?? $response->json();
                if (!is_array($raw)) {
                    Log::warning('LabIntegrationController: Provider catalog payload format invalid, fallback to sample', [
                        'provider' => $provider,
                    ]);
                    return $this->getSampleTests($provider, $search);
                }

                $normalized = collect($raw)
                    ->map(function ($test) {
                        if (!is_array($test)) {
                            return null;
                        }
                        return [
                            'code' => $test['code'] ?? $test['test_code'] ?? null,
                            'name' => $test['name'] ?? $test['test_name'] ?? null,
                            'price' => (float) ($test['price'] ?? $test['mrp'] ?? 0),
                            'category' => $test['category'] ?? $test['department'] ?? null,
                        ];
                    })
                    ->filter(fn ($t) => !empty($t['code']) && !empty($t['name']))
                    ->values()
                    ->all();

                Log::info('LabIntegrationController: Provider catalog fetched successfully', [
                    'provider' => $provider,
                    'count' => count($normalized),
                ]);

                if (!empty($normalized)) {
                    return $normalized;
                }
            } else {
                Log::error('LabIntegrationController: Provider catalog API call failed, fallback to sample', [
                    'provider' => $provider,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('LabIntegrationController: Provider catalog API exception, fallback to sample', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->getSampleTests($provider, $search);
    }

    private function submitToProvider(string $provider, string $orderNumber, array $validated, int $clinicId): ?string
    {
        $providerConfig = config("services.labs.{$provider}", []);
        $apiKey = (string) ($providerConfig['api_key'] ?? '');
        $apiBase = (string) ($providerConfig['api_base'] ?? ($this->labProviders[$provider]['api_base'] ?? ''));

        Log::info('LabIntegrationController: Submit to provider attempt', [
            'provider' => $provider,
            'order_number' => $orderNumber,
            'has_api_key' => !empty($apiKey),
        ]);

        if (empty($apiKey) || empty($apiBase)) {
            Log::warning('LabIntegrationController: Provider API not configured, skipping external order submit', [
                'provider' => $provider,
                'order_number' => $orderNumber,
            ]);
            return null;
        }

        try {
            $payload = [
                'clinic_id' => $clinicId,
                'internal_order_number' => $orderNumber,
                'patient_id' => $validated['patient_id'],
                'visit_id' => $validated['visit_id'] ?? null,
                'tests' => $validated['tests'],
                'sample_collection_type' => $validated['sample_collection_type'],
                'collection_date' => $validated['collection_date'] ?? null,
                'collection_address' => $validated['collection_address'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ];

            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post(rtrim($apiBase, '/') . '/orders', $payload);

            if ($response->successful()) {
                $externalOrderId = $response->json('order_id')
                    ?? $response->json('id')
                    ?? $response->json('data.order_id')
                    ?? null;

                Log::info('LabIntegrationController: Provider order submitted successfully', [
                    'provider' => $provider,
                    'order_number' => $orderNumber,
                    'external_order_id' => $externalOrderId,
                ]);
                return $externalOrderId ? (string) $externalOrderId : null;
            }

            Log::error('LabIntegrationController: Provider order submit failed', [
                'provider' => $provider,
                'order_number' => $orderNumber,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('LabIntegrationController: Provider order submit exception', [
                'provider' => $provider,
                'order_number' => $orderNumber,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
