<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Clinic\ClinicController;
use App\Http\Controllers\Patients\PatientController;
use App\Http\Controllers\EMR\EmrController;
use App\Http\Controllers\Scheduling\AppointmentController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Prescription\PrescriptionController;
use App\Http\Controllers\WhatsApp\WhatsAppController;
use App\Http\Controllers\Abdm\AbdmController;
use App\Http\Controllers\Photo\PhotoVaultController;
use App\Http\Controllers\Vendor\LabOrderController;
use App\Http\Controllers\Analytics\AnalyticsController;
use App\Http\Controllers\AI\AiAssistantController;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| ClinicOS API Routes — Laravel 11
| Base URL: /api/v1
| Auth: Bearer token (Sanctum)
|--------------------------------------------------------------------------
*/

// ── Public routes ──────────────────────────────────────────────────────────

Route::prefix('v1')->group(function () {

    // Health check endpoint
    Route::get('health', function () {
        Log::info('Health check endpoint called');
        return response()->json([
            'status' => 'ok',
            'app' => 'ClinicOS API',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ]);
    });

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('register',         [AuthController::class, 'register']);
        Route::post('login',            [AuthController::class, 'login']);
        Route::post('refresh',          [AuthController::class, 'refresh']);
        Route::post('forgot-password',  [AuthController::class, 'forgotPassword']);
        Route::post('reset-password',   [AuthController::class, 'resetPassword']);
    });

    // ABDM webhooks (unauthenticated — NHA-signed requests)
    Route::prefix('abdm/webhook')->group(function () {
        Route::post('consent',          [AbdmController::class, 'consentWebhook']);
        Route::post('health-info',      [AbdmController::class, 'healthInfoWebhook']);
        Route::post('notify',           [AbdmController::class, 'notifyWebhook']);
    });

    // WhatsApp inbound webhook (Meta signature verified inside controller)
    Route::post('whatsapp/webhook',     [WhatsAppController::class, 'inbound']);
    Route::get('whatsapp/webhook',      [WhatsAppController::class, 'verify']); // Meta challenge

    // Online patient booking (public)
    Route::prefix('booking/{clinicSlug}')->group(function () {
        Route::get('slots',             [AppointmentController::class, 'publicSlots']);
        Route::post('book',             [AppointmentController::class, 'publicBook']);
        Route::post('confirm',          [AppointmentController::class, 'publicConfirm']);
    });

    // ── Authenticated routes ───────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout',      [AuthController::class, 'logout']);
        Route::get('auth/me',           [AuthController::class, 'me']);

        // ── Clinic / Tenant setup ─────────────────────────────────────────
        Route::prefix('clinic')->group(function () {
            Route::get('/',             [ClinicController::class, 'show']);
            Route::put('/',             [ClinicController::class, 'update']);
            Route::get('staff',         [ClinicController::class, 'staff']);
            Route::post('staff',        [ClinicController::class, 'addStaff']);
            Route::delete('staff/{id}', [ClinicController::class, 'removeStaff']);
            Route::get('doctors',       [ClinicController::class, 'doctors']);
            Route::post('doctors',      [ClinicController::class, 'addDoctor']);
            Route::get('rooms',         [ClinicController::class, 'rooms']);
            Route::post('rooms',        [ClinicController::class, 'addRoom']);
            Route::get('equipment',     [ClinicController::class, 'equipment']);
            Route::post('equipment',    [ClinicController::class, 'addEquipment']);
        });

        // ── Patients ──────────────────────────────────────────────────────
        Route::prefix('patients')->group(function () {
            Route::get('/',             [PatientController::class, 'index']);   // search/list
            Route::post('/',            [PatientController::class, 'store']);
            Route::get('{id}',          [PatientController::class, 'show']);
            Route::put('{id}',          [PatientController::class, 'update']);
            Route::get('{id}/timeline', [PatientController::class, 'timeline']);
            Route::get('{id}/visits',   [PatientController::class, 'visits']);
            Route::get('{id}/photos',   [PhotoVaultController::class, 'byPatient']);
            Route::post('{id}/abha',    [AbdmController::class, 'linkAbha']);    // link/create ABHA
        });

        // ── Appointments / Scheduling ─────────────────────────────────────
        Route::prefix('appointments')->group(function () {
            Route::get('/',             [AppointmentController::class, 'index']);  // ?date=&doctor_id=
            Route::post('/',            [AppointmentController::class, 'store']);
            Route::get('{id}',          [AppointmentController::class, 'show']);
            Route::put('{id}',          [AppointmentController::class, 'update']);
            Route::delete('{id}',       [AppointmentController::class, 'cancel']);
            Route::post('{id}/check-in',[AppointmentController::class, 'checkIn']);
            Route::post('{id}/complete',[AppointmentController::class, 'complete']);
            Route::get('slots',         [AppointmentController::class, 'availableSlots']); // ?doctor_id=&date=&service=
            Route::get('queue',         [AppointmentController::class, 'queue']);           // live queue
        });

        // ── EMR / Clinical Notes ──────────────────────────────────────────
        Route::prefix('emr')->group(function () {
            Route::get('visits/{visitId}',          [EmrController::class, 'show']);
            Route::post('visits',                   [EmrController::class, 'create']);
            Route::put('visits/{visitId}',          [EmrController::class, 'update']);
            Route::post('visits/{visitId}/finalise',[EmrController::class, 'finalise']);

            // Specialty templates
            Route::get('templates/{specialty}',     [EmrController::class, 'template']);    // derm|physio|dental|...

            // Lesion annotations
            Route::post('visits/{visitId}/lesions', [EmrController::class, 'addLesion']);
            Route::delete('visits/{visitId}/lesions/{lesionId}', [EmrController::class, 'removeLesion']);

            // Grading scales (PASI, IGA, DLQI, ROM, MMT…)
            Route::post('visits/{visitId}/scales',  [EmrController::class, 'saveScales']);

            // Procedures
            Route::post('visits/{visitId}/procedures', [EmrController::class, 'addProcedure']);

            // Dental tooth chart
            Route::get('dental/{patientId}/chart',  [EmrController::class, 'dentalChart']);
            Route::put('dental/{patientId}/tooth/{toothCode}', [EmrController::class, 'updateTooth']);
        });

        // ── Prescriptions ─────────────────────────────────────────────────
        Route::prefix('prescriptions')->group(function () {
            Route::get('/',             [PrescriptionController::class, 'index']);
            Route::post('/',            [PrescriptionController::class, 'store']);
            Route::get('{id}',          [PrescriptionController::class, 'show']);
            Route::post('{id}/send',    [PrescriptionController::class, 'send']);   // WhatsApp + FHIR
            Route::get('{id}/pdf',      [PrescriptionController::class, 'pdf']);
        });

        // Drug database search
        Route::get('drugs/search',      [PrescriptionController::class, 'drugSearch']);   // ?q=

        // ── Photo Vault ───────────────────────────────────────────────────
        Route::prefix('photos')->group(function () {
            Route::get('/',             [PhotoVaultController::class, 'index']);
            Route::post('/',            [PhotoVaultController::class, 'upload']);   // multipart/form-data
            Route::get('{id}',          [PhotoVaultController::class, 'show']);
            Route::delete('{id}',       [PhotoVaultController::class, 'destroy']);
            Route::get('compare',       [PhotoVaultController::class, 'compare']); // ?patient_id=&region=&visit_ids[]=
        });

        // ── Billing ───────────────────────────────────────────────────────
        Route::prefix('invoices')->group(function () {
            Route::get('/',             [InvoiceController::class, 'index']);
            Route::post('/',            [InvoiceController::class, 'store']);
            Route::get('{id}',          [InvoiceController::class, 'show']);
            Route::put('{id}',          [InvoiceController::class, 'update']);
            Route::post('{id}/send',    [InvoiceController::class, 'sendLink']);     // WhatsApp payment link
            Route::get('{id}/pdf',      [InvoiceController::class, 'pdf']);
            Route::get('{id}/gst',      [InvoiceController::class, 'gstBreakdown']); // SAC code breakdown
        });

        Route::prefix('payments')->group(function () {
            Route::get('/',             [PaymentController::class, 'index']);
            Route::post('razorpay/order',   [PaymentController::class, 'createRazorpayOrder']);
            Route::post('razorpay/verify',  [PaymentController::class, 'verifyPayment']);
            Route::get('outstanding',       [PaymentController::class, 'outstanding']);
        });

        // GST
        Route::get('gst/report',        [InvoiceController::class, 'gstReport']);     // ?month=&year=
        Route::post('gst/einvoice/{id}',[InvoiceController::class, 'generateEinvoice']); // IRN via GSP

        // ── WhatsApp ──────────────────────────────────────────────────────
        Route::prefix('whatsapp')->group(function () {
            Route::get('messages',      [WhatsAppController::class, 'messages']);
            Route::post('send',         [WhatsAppController::class, 'send']);
            Route::post('template',     [WhatsAppController::class, 'sendTemplate']); // appointment/rx/payment
            Route::get('threads/{patientId}', [WhatsAppController::class, 'thread']);
        });

        // ── ABDM ──────────────────────────────────────────────────────────
        Route::prefix('abdm')->group(function () {
            Route::post('verify-abha',  [AbdmController::class, 'verifyAbha']);
            Route::post('link-abha',    [AbdmController::class, 'linkAbha']);
            Route::post('request-consent', [AbdmController::class, 'requestConsent']);
            Route::post('push-record',  [AbdmController::class, 'pushRecord']);
            Route::post('care-context', [AbdmController::class, 'createCareContext']);
            Route::get('hi-types',      [AbdmController::class, 'getHiTypes']);
            Route::get('patients/{patientId}/consents', [AbdmController::class, 'getPatientConsents']);
            Route::get('patients/{patientId}/care-contexts', [AbdmController::class, 'getPatientCareContexts']);
        });

        // ── AI Assistant ──────────────────────────────────────────────────
        Route::prefix('ai')->group(function () {
            Route::post('transcribe',           [AiAssistantController::class, 'transcribe']);      // audio → text (Whisper)
            Route::post('map-fields',           [AiAssistantController::class, 'mapToEmrFields']);  // text → EMR fields (Claude)
            Route::post('summarise/{visitId}',  [AiAssistantController::class, 'summarise']);       // EMR → patient summary
            Route::post('suggest-rx',           [AiAssistantController::class, 'suggestRx']);       // diagnosis → Rx suggestion
        });

        // ── Vendor / Lab ──────────────────────────────────────────────────
        Route::prefix('lab-orders')->group(function () {
            Route::get('/',             [LabOrderController::class, 'index']);
            Route::post('/',            [LabOrderController::class, 'store']);
            Route::get('{id}',          [LabOrderController::class, 'show']);
            Route::put('{id}/status',   [LabOrderController::class, 'updateStatus']);
            Route::post('{id}/result',  [LabOrderController::class, 'uploadResult']);  // PDF upload → S3
            Route::post('{id}/send',    [LabOrderController::class, 'sendResult']);    // to clinic + patient + FHIR
        });

        // ── Analytics ────────────────────────────────────────────────────
        Route::prefix('analytics')->group(function () {
            Route::get('dashboard',     [AnalyticsController::class, 'dashboard']);   // KPI summary
            Route::get('revenue',       [AnalyticsController::class, 'revenue']);     // ?from=&to=&group_by=
            Route::get('appointments',  [AnalyticsController::class, 'appointments']);
            Route::get('patients',      [AnalyticsController::class, 'patients']);    // retention, recall
            Route::get('doctors',       [AnalyticsController::class, 'doctors']);     // per-doctor productivity
            Route::get('specialty',     [AnalyticsController::class, 'specialty']);   // specialty-specific KPIs
        });

    }); // end auth middleware

}); // end v1 prefix
