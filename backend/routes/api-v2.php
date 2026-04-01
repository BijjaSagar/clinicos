<?php

/**
 * ClinicOS API v2 — modular tenant-aware routes (Laravel 11).
 *
 * Full path prefix: /api/v2 (see bootstrap/app.php).
 * Each block uses clinic.module:* aligned with config/clinic_modules.php.
 */
use App\Http\Controllers\AI\AiAssistantController;
use App\Http\Controllers\Abdm\AbdmController;
use App\Http\Controllers\Analytics\AnalyticsController;
use App\Http\Controllers\Api\V2\BootstrapController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\EMR\EmrController;
use App\Http\Controllers\Patients\PatientController;
use App\Http\Controllers\Photo\PhotoVaultController;
use App\Http\Controllers\Prescription\PrescriptionController;
use App\Http\Controllers\Scheduling\AppointmentController;
use App\Http\Controllers\Vendor\LabOrderController;
use App\Http\Controllers\WhatsApp\WhatsAppController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::prefix('v2')->group(function () {

    Route::get('/health', function () {
        Log::info('API v2 health check');

        return response()->json([
            'status' => 'ok',
            'app' => 'ClinicOS API',
            'version' => config('clinicos_api_v2.version', '2.0.0'),
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    Route::middleware(['auth:sanctum', 'clinic.tenant'])->group(function () {

        Route::get('/bootstrap', [BootstrapController::class, 'show']);

        /* ── Core (scheduling + patients) — always on ───────────────────── */
        Route::prefix('patients')->group(function () {
            Route::get('/', [PatientController::class, 'index']);
            Route::post('/', [PatientController::class, 'store']);
            Route::get('{id}', [PatientController::class, 'show'])->whereNumber('id');
            Route::put('{id}', [PatientController::class, 'update'])->whereNumber('id');
        });

        Route::prefix('appointments')->group(function () {
            Route::get('/', [AppointmentController::class, 'index']);
            Route::post('/', [AppointmentController::class, 'store']);
            Route::get('slots', [AppointmentController::class, 'availableSlots']);
            Route::get('queue', [AppointmentController::class, 'queue']);
            Route::get('{id}', [AppointmentController::class, 'show'])->whereNumber('id');
            Route::put('{id}', [AppointmentController::class, 'update'])->whereNumber('id');
            Route::delete('{id}', [AppointmentController::class, 'cancel'])->whereNumber('id');
        });

        /* ── clinical_emr ─────────────────────────────────────────────────── */
        Route::middleware(['clinic.module:clinical_emr'])->prefix('emr')->group(function () {
            Route::get('templates/{specialty}', [EmrController::class, 'template']);
            Route::get('visits/{visitId}', [EmrController::class, 'show'])->whereNumber('visitId');
            Route::post('visits', [EmrController::class, 'create']);
            Route::put('visits/{visitId}', [EmrController::class, 'update'])->whereNumber('visitId');
            Route::post('visits/{visitId}/finalise', [EmrController::class, 'finalise'])->whereNumber('visitId');
            Route::post('visits/{visitId}/lesions', [EmrController::class, 'addLesion'])->whereNumber('visitId');
            Route::post('visits/{visitId}/scales', [EmrController::class, 'saveScales'])->whereNumber('visitId');
            Route::put('dental/{patientId}/tooth/{toothCode}', [EmrController::class, 'updateTooth'])->whereNumber('patientId');
        });

        /* ── prescriptions ───────────────────────────────────────────────── */
        Route::middleware(['clinic.module:prescriptions'])->group(function () {
            Route::prefix('prescriptions')->group(function () {
                Route::get('/', [PrescriptionController::class, 'index']);
                Route::post('/', [PrescriptionController::class, 'store']);
                Route::get('{id}', [PrescriptionController::class, 'show'])->whereNumber('id');
                Route::post('{id}/send', [PrescriptionController::class, 'send'])->whereNumber('id');
                Route::get('{id}/pdf', [PrescriptionController::class, 'pdf'])->whereNumber('id');
            });
            Route::get('drugs/search', [PrescriptionController::class, 'drugSearch']);
        });

        /* ── clinical_media (photo vault) ─────────────────────────────────── */
        Route::middleware(['clinic.module:clinical_media'])->prefix('photos')->group(function () {
            Route::get('/', [PhotoVaultController::class, 'index']);
            Route::post('/', [PhotoVaultController::class, 'upload']);
            Route::get('compare', [PhotoVaultController::class, 'compare']);
            Route::get('{id}', [PhotoVaultController::class, 'show'])->whereNumber('id');
            Route::delete('{id}', [PhotoVaultController::class, 'destroy'])->whereNumber('id');
        });

        /* ── billing_core ─────────────────────────────────────────────────── */
        Route::middleware(['clinic.module:billing_core'])->group(function () {
            Route::prefix('invoices')->group(function () {
                Route::get('/', [InvoiceController::class, 'index']);
                Route::post('/', [InvoiceController::class, 'store']);
                Route::get('{id}', [InvoiceController::class, 'show'])->whereNumber('id');
                Route::put('{id}', [InvoiceController::class, 'update'])->whereNumber('id');
                Route::post('{id}/send', [InvoiceController::class, 'sendLink'])->whereNumber('id');
                Route::get('{id}/pdf', [InvoiceController::class, 'pdf'])->whereNumber('id');
                Route::get('{id}/gst', [InvoiceController::class, 'gstBreakdown'])->whereNumber('id');
            });
            Route::prefix('payments')->group(function () {
                Route::get('/', [PaymentController::class, 'index']);
                Route::post('razorpay/order', [PaymentController::class, 'createRazorpayOrder']);
                Route::post('razorpay/verify', [PaymentController::class, 'verifyPayment']);
                Route::get('outstanding', [PaymentController::class, 'outstanding']);
            });
        });

        /* ── billing_gst_india ───────────────────────────────────────────── */
        Route::middleware(['clinic.module:billing_gst_india'])->group(function () {
            Route::get('gst/report', [InvoiceController::class, 'gstReport']);
            Route::post('gst/einvoice/{id}', [InvoiceController::class, 'generateEinvoice'])->whereNumber('id');
        });

        /* ── messaging_whatsapp ────────────────────────────────────────────── */
        Route::middleware(['clinic.module:messaging_whatsapp'])->prefix('whatsapp')->group(function () {
            Route::get('messages', [WhatsAppController::class, 'messages']);
            Route::post('send', [WhatsAppController::class, 'send']);
            Route::post('template', [WhatsAppController::class, 'sendTemplate']);
            Route::get('threads/{patientId}', [WhatsAppController::class, 'thread'])->whereNumber('patientId');
        });

        /* ── abdm_india ────────────────────────────────────────────────────── */
        Route::middleware(['clinic.module:abdm_india'])->prefix('abdm')->group(function () {
            Route::post('verify-abha', [AbdmController::class, 'verifyAbha']);
            Route::post('link-abha', [AbdmController::class, 'linkAbha']);
            Route::post('request-consent', [AbdmController::class, 'requestConsent']);
            Route::post('push-record', [AbdmController::class, 'pushRecord']);
            Route::post('care-context', [AbdmController::class, 'createCareContext']);
            Route::get('hi-types', [AbdmController::class, 'getHiTypes']);
            Route::get('patients/{patientId}/consents', [AbdmController::class, 'getPatientConsents'])->whereNumber('patientId');
            Route::get('patients/{patientId}/care-contexts', [AbdmController::class, 'getPatientCareContexts'])->whereNumber('patientId');
        });

        /* ── ai_documentation ─────────────────────────────────────────────── */
        Route::middleware(['clinic.module:ai_documentation'])->prefix('ai')->group(function () {
            Route::post('transcribe', [AiAssistantController::class, 'transcribe']);
            Route::post('map-fields', [AiAssistantController::class, 'mapToEmrFields']);
            Route::post('summarise/{visitId}', [AiAssistantController::class, 'summarise'])->whereNumber('visitId');
            Route::post('suggest-rx', [AiAssistantController::class, 'suggestRx']);
        });

        /* ── lab_orders ───────────────────────────────────────────────────── */
        Route::middleware(['clinic.module:lab_orders'])->prefix('lab-orders')->group(function () {
            Route::get('/', [LabOrderController::class, 'index']);
            Route::post('/', [LabOrderController::class, 'store']);
            Route::get('{id}', [LabOrderController::class, 'show'])->whereNumber('id');
            Route::put('{id}/status', [LabOrderController::class, 'updateStatus'])->whereNumber('id');
            Route::post('{id}/result', [LabOrderController::class, 'uploadResult'])->whereNumber('id');
            Route::post('{id}/send', [LabOrderController::class, 'sendResult'])->whereNumber('id');
        });

        /* ── analytics ───────────────────────────────────────────────────── */
        Route::middleware(['clinic.module:analytics'])->prefix('analytics')->group(function () {
            Route::get('dashboard', [AnalyticsController::class, 'dashboard']);
            Route::get('revenue', [AnalyticsController::class, 'revenue']);
            Route::get('appointments', [AnalyticsController::class, 'appointments']);
            Route::get('patients', [AnalyticsController::class, 'patients']);
            Route::get('doctors', [AnalyticsController::class, 'doctors']);
            Route::get('specialty', [AnalyticsController::class, 'specialty']);
        });
    });
});
