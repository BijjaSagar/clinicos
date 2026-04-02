<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\PatientWebController;
use App\Http\Controllers\Web\AppointmentWebController;
use App\Http\Controllers\Web\EmrWebController;
use App\Http\Controllers\Web\BillingWebController;
use App\Http\Controllers\Web\VendorWebController;
use App\Http\Controllers\Web\WhatsAppWebController;
use App\Http\Controllers\Web\AnalyticsWebController;
use App\Http\Controllers\Web\SettingsWebController;
use App\Http\Controllers\Web\PaymentWebController;
use App\Http\Controllers\Web\GstReportWebController;
use App\Http\Controllers\Web\PhotoVaultWebController;
use App\Http\Controllers\Web\PrescriptionWebController;
use App\Http\Controllers\Web\ClinicUserController;
use App\Http\Controllers\Web\PublicBookingController;
use App\Http\Controllers\Web\AiDocumentationController;
use App\Http\Controllers\Web\InsuranceController;
use App\Http\Controllers\Web\AbdmHipController;
use App\Http\Controllers\Web\LabIntegrationController;
use App\Http\Controllers\Web\MultiLocationController;
use App\Http\Controllers\Web\CustomEmrBuilderController;
use App\Http\Controllers\Web\ReferralWebController;
use App\Http\Controllers\Web\WearableWebController;
use App\Http\Controllers\Web\ComplianceWebController;
use App\Http\Controllers\Web\AbdmHiuController;
use App\Http\Controllers\Web\AppShellController;
use App\Http\Controllers\Web\SubscriptionController;
use App\Http\Controllers\Web\IpdController;
use App\Http\Controllers\Web\PharmacyController;
use App\Http\Controllers\Web\LabController;
use App\Http\Controllers\Web\LabTechnicianController;
use App\Http\Controllers\Web\OpdController;
use App\Http\Controllers\Web\HospitalSettingsController;
use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\SetupWizardController;
use App\Http\Controllers\Web\WhatsAppSettingsController;

// Health check for monitoring
Route::get('/health', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'database' => 'connected',
            'version' => config('app.version', '2.0.0'),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'timestamp' => now()->toIso8601String(),
            'database' => 'disconnected',
        ], 503);
    }
})->name('health');

// Landing page
Route::get('/', fn() => view('welcome'))->name('home');

// Auth routes (guest only)
Route::middleware(['guest', 'throttle:auth'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ═══════════════════════════════════════════════════════════════════════
    // ALL ROLES - Dashboard & Schedule (everyone can access)
    // ═══════════════════════════════════════════════════════════════════════
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/setup', [SetupWizardController::class, 'index'])->name('setup-wizard.index');
    Route::post('/setup/save', [SetupWizardController::class, 'saveStep'])->name('setup-wizard.save');
    Route::get('/app', [AppShellController::class, 'index'])->name('app.home');
    Route::get('/schedule', [AppointmentWebController::class, 'index'])->name('schedule');

    // ═══════════════════════════════════════════════════════════════════════
    // PATIENTS - owner, doctor, receptionist, nurse
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor,receptionist,nurse'])->group(function () {
        Route::prefix('patients')->name('patients.')->group(function () {
            Route::get('/', [PatientWebController::class, 'index'])->name('index');
            Route::get('/create', [PatientWebController::class, 'create'])->name('create');
            Route::post('/', [PatientWebController::class, 'store'])->name('store');
            Route::get('/{patient}', [PatientWebController::class, 'show'])->name('show');
            Route::get('/{patient}/edit', [PatientWebController::class, 'edit'])->name('edit');
            Route::put('/{patient}', [PatientWebController::class, 'update'])->name('update');
            Route::delete('/{patient}', [PatientWebController::class, 'destroy'])->name('destroy');
            Route::post('/{patient}/upload-photo', [PatientWebController::class, 'uploadPhoto'])->name('upload-photo');
            Route::get('/{patient}/photos/{photo}', [PatientWebController::class, 'viewPhoto'])->name('view-photo');
            Route::delete('/{patient}/photos/{photo}', [PatientWebController::class, 'deletePhoto'])->name('delete-photo');
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // APPOINTMENTS - owner, doctor, receptionist, nurse
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor,receptionist,nurse'])->group(function () {
        Route::prefix('appointments')->name('appointments.')->group(function () {
            Route::get('/', [AppointmentWebController::class, 'index'])->name('index');
            Route::get('/create', [AppointmentWebController::class, 'create'])->name('create');
            Route::post('/', [AppointmentWebController::class, 'store'])->name('store');
            Route::get('/{appointment}', [AppointmentWebController::class, 'show'])->name('show');
            Route::put('/{appointment}/status', [AppointmentWebController::class, 'updateStatus'])->name('status');
            Route::delete('/{appointment}', [AppointmentWebController::class, 'destroy'])->name('destroy');
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // EMR / NOTES - owner, doctor, nurse (NOT receptionist)
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor,nurse'])->group(function () {
        Route::prefix('emr')->name('emr.')->group(function () {
            Route::get('/', [EmrWebController::class, 'index'])->name('index');
            Route::get('/{patient}/{visit}', [EmrWebController::class, 'show'])->name('show');
            Route::post('/{patient}/create', [EmrWebController::class, 'create'])->name('create');
            Route::patch('/{patient}/{visit}', [EmrWebController::class, 'update'])->name('update');
            Route::post('/{patient}/{visit}/finalise', [EmrWebController::class, 'finalise'])->name('finalise');
            
            // EMR sub-features
            Route::post('/{patient}/{visit}/lesions', [EmrWebController::class, 'addLesion'])->name('add-lesion');
            Route::delete('/{patient}/{visit}/lesions/{lesion}', [EmrWebController::class, 'removeLesion'])->name('remove-lesion');
            Route::post('/{patient}/{visit}/scales', [EmrWebController::class, 'saveScales'])->name('save-scales');
            Route::post('/{patient}/{visit}/procedures', [EmrWebController::class, 'saveProcedures'])->name('save-procedures');
            Route::post('/{patient}/{visit}/prescription', [EmrWebController::class, 'savePrescription'])->name('save-prescription');
            Route::post('/{patient}/{visit}/custom-fields', [EmrWebController::class, 'saveCustomFields'])->name('save-custom-fields');
        });
        
        // Drug Search API (for EMR)
        Route::get('/api/drugs/search', [EmrWebController::class, 'searchDrugs'])->name('api.drugs.search');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // ABDM - owner, doctor
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor'])->group(function () {
        Route::prefix('abdm')->name('abdm.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Web\AbdmWebController::class, 'index'])->name('index');
            
            // ABHA Creation APIs
            Route::post('/aadhaar/generate-otp', [\App\Http\Controllers\Web\AbdmWebController::class, 'generateAadhaarOtp'])->name('aadhaar.otp');
            Route::post('/aadhaar/verify-otp', [\App\Http\Controllers\Web\AbdmWebController::class, 'verifyAadhaarOtp'])->name('aadhaar.verify');
            Route::post('/mobile/generate-otp', [\App\Http\Controllers\Web\AbdmWebController::class, 'generateMobileOtp'])->name('mobile.otp');
            Route::post('/mobile/verify-otp', [\App\Http\Controllers\Web\AbdmWebController::class, 'verifyMobileOtp'])->name('mobile.verify');
            
            // ABHA Linking
            Route::post('/link', [\App\Http\Controllers\Web\AbdmWebController::class, 'linkAbha'])->name('link');
            Route::post('/search', [\App\Http\Controllers\Web\AbdmWebController::class, 'searchAbha'])->name('search');
            
            // Facility QR
            Route::get('/facility-qr', [\App\Http\Controllers\Web\AbdmWebController::class, 'getFacilityQr'])->name('facility-qr');
        });
        
        // ABDM M2 - HIP (Health Information Provider)
        Route::prefix('abdm/hip')->name('abdm.hip.')->group(function () {
            Route::get('/', [AbdmHipController::class, 'index'])->name('index');
            Route::post('/register', [AbdmHipController::class, 'registerHIP'])->name('register');
            Route::post('/link/{patient}', [AbdmHipController::class, 'linkCareContext'])->name('link');
            Route::post('/consent/respond', [AbdmHipController::class, 'respondToConsent'])->name('consent.respond');
            Route::get('/fhir/{visit}', [AbdmHipController::class, 'generateFHIRBundle'])->name('fhir');
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // BILLING / INVOICES - owner, doctor, receptionist
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor,receptionist'])->group(function () {
        Route::prefix('billing')->name('billing.')->group(function () {
            Route::get('/', [BillingWebController::class, 'index'])->name('index');
            Route::get('/create', [BillingWebController::class, 'create'])->name('create');
            Route::post('/', [BillingWebController::class, 'store'])->name('store');
            Route::get('/{invoice}', [BillingWebController::class, 'show'])->name('show');
            Route::get('/{invoice}/pdf', [BillingWebController::class, 'pdf'])->name('pdf');
            Route::post('/{invoice}/send-whatsapp', [BillingWebController::class, 'sendWhatsApp'])->name('send-whatsapp');
            Route::post('/{invoice}/mark-paid', [BillingWebController::class, 'markPaid'])->name('mark-paid');
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // PAYMENTS - owner, receptionist
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:receptionist'])->group(function () {
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [PaymentWebController::class, 'index'])->name('index');
            
            // Razorpay Integration
            Route::post('/invoice/{invoice}/create-order', [PaymentWebController::class, 'createOrder'])->name('create-order');
            Route::post('/invoice/{invoice}/payment-link', [PaymentWebController::class, 'createPaymentLink'])->name('payment-link');
            Route::post('/invoice/{invoice}/qr-code', [PaymentWebController::class, 'createQRCode'])->name('qr-code');
            Route::post('/verify', [PaymentWebController::class, 'verifyPayment'])->name('verify');
            Route::post('/{payment}/refund', [PaymentWebController::class, 'initiateRefund'])->name('refund');
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // GST REPORTS - owner, receptionist
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:receptionist'])->group(function () {
        Route::get('/gst-reports', [GstReportWebController::class, 'index'])->name('gst-reports.index');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // WHATSAPP - owner, doctor, receptionist
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor,receptionist'])->group(function () {
        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
            Route::get('/', [WhatsAppWebController::class, 'index'])->name('index');
            Route::post('/send', [WhatsAppWebController::class, 'send'])->name('send');
            Route::post('/broadcast', [WhatsAppWebController::class, 'broadcast'])->name('broadcast');
            
            // Appointment reminders
            Route::post('/appointment/{appointment}/reminder', [WhatsAppWebController::class, 'sendAppointmentReminder'])->name('appointment.reminder');
            Route::post('/appointments/bulk-reminders', [WhatsAppWebController::class, 'sendBulkReminders'])->name('bulk-reminders');
            Route::get('/reminders/upcoming', [WhatsAppWebController::class, 'getUpcomingReminders'])->name('reminders.upcoming');
            
            // Prescription
            Route::post('/visit/{visit}/prescription', [WhatsAppWebController::class, 'sendPrescription'])->name('prescription');
            
            // Templates
            Route::get('/templates', [WhatsAppWebController::class, 'getTemplates'])->name('templates');
            Route::post('/templates', [WhatsAppWebController::class, 'saveTemplate'])->name('templates.save');
            Route::delete('/templates/{template}', [WhatsAppWebController::class, 'deleteTemplate'])->name('templates.delete');
            
            // Reminder settings
            Route::post('/reminders/schedule', [WhatsAppWebController::class, 'scheduleReminder'])->name('reminders.schedule');
            Route::post('/teleconsult', [WhatsAppWebController::class, 'sendTeleconsult'])->name('teleconsult');
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // REFERRALS — doctor, receptionist
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor,receptionist'])->prefix('referrals')->name('referrals.')->group(function () {
        Route::get('/', [ReferralWebController::class, 'index'])->name('index');
        Route::post('/', [ReferralWebController::class, 'store'])->name('store');
        Route::post('/{referral}/status', [ReferralWebController::class, 'updateStatus'])->name('status');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // WEARABLES + COMPLIANCE + ABDM HIU — doctor
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor'])->group(function () {
        Route::get('/wearables', [WearableWebController::class, 'index'])->name('wearables.index');
        Route::post('/wearables/import', [WearableWebController::class, 'importCsv'])->name('wearables.import');
        Route::get('/compliance/nabh', [ComplianceWebController::class, 'nabh'])->name('compliance.nabh');
        Route::prefix('abdm/hiu')->name('abdm.hiu.')->group(function () {
            Route::get('/', [AbdmHiuController::class, 'index'])->name('index');
            Route::post('/', [AbdmHiuController::class, 'store'])->name('store');
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // ANALYTICS - owner, doctor
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor'])->group(function () {
        Route::get('/analytics', [AnalyticsWebController::class, 'index'])->name('analytics.index');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // SETTINGS - owner ONLY (owner check happens in middleware - owner bypasses)
    // ═══════════════════════════════════════════════════════════════════════
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsWebController::class, 'index'])->name('index')->middleware('role:owner');
        Route::post('/clinic', [SettingsWebController::class, 'updateClinic'])->name('clinic')->middleware('role:owner');
        Route::post('/billing', [SettingsWebController::class, 'updateBilling'])->name('billing')->middleware('role:owner');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // LAB ORDERS (Vendor) - owner, doctor
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor'])->group(function () {
        Route::prefix('vendor')->name('vendor.')->group(function () {
            Route::get('/', [VendorWebController::class, 'index'])->name('index');
            Route::post('/orders/{order}/accept', [VendorWebController::class, 'acceptOrder'])->name('accept');
            Route::post('/orders/{order}/upload', [VendorWebController::class, 'uploadResult'])->name('upload');
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // PHOTO VAULT - owner, doctor, nurse
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor,nurse'])->group(function () {
        Route::prefix('photo-vault')->name('photo-vault.')->group(function () {
            Route::get('/', [PhotoVaultWebController::class, 'index'])->name('index');
            Route::post('/upload', [PhotoVaultWebController::class, 'upload'])->name('upload');
            Route::put('/{photo}', [PhotoVaultWebController::class, 'update'])->name('update');
            Route::delete('/{photo}', [PhotoVaultWebController::class, 'destroy'])->name('destroy');
            Route::get('/patient/{patient}/comparison', [PhotoVaultWebController::class, 'comparison'])->name('comparison');
            Route::get('/patient/{patient}/timeline', [PhotoVaultWebController::class, 'timeline'])->name('timeline');
            Route::get('/body-regions', [PhotoVaultWebController::class, 'getBodyRegions'])->name('body-regions');
            Route::post('/create-pair', [PhotoVaultWebController::class, 'createPair'])->name('create-pair');
            Route::post('/consent', [PhotoVaultWebController::class, 'recordConsent'])->name('consent');
            Route::get('/patient/{patient}/consent', [PhotoVaultWebController::class, 'checkConsent'])->name('consent.check');
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // PRESCRIPTIONS - owner, doctor, nurse
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor,nurse'])->group(function () {
        Route::prefix('prescriptions')->name('prescriptions.')->group(function () {
            Route::get('/', [PrescriptionWebController::class, 'index'])->name('index');
            
            // Drug search API
            Route::get('/drugs/search', [PrescriptionWebController::class, 'searchDrugs'])->name('drugs.search');
            Route::post('/drugs/interactions', [PrescriptionWebController::class, 'checkInteractions'])->name('drugs.interactions');
            
            // Prescription actions
            Route::post('/visit/{visit}/save', [PrescriptionWebController::class, 'savePrescription'])->name('save');
            Route::get('/visit/{visit}/pdf', [PrescriptionWebController::class, 'generatePdf'])->name('pdf');
            Route::get('/visit/{visit}/spectacle-pdf', [PrescriptionWebController::class, 'spectaclePdf'])->name('spectacle-pdf');
            Route::get('/visit/{visit}/contact-lens-pdf', [PrescriptionWebController::class, 'contactLensPdf'])->name('contact-lens-pdf');
            Route::post('/visit/{visit}/whatsapp', [PrescriptionWebController::class, 'sendWhatsApp'])->name('whatsapp');
            
            // Templates
            Route::get('/templates', [PrescriptionWebController::class, 'getTemplates'])->name('templates');
            Route::post('/templates', [PrescriptionWebController::class, 'saveTemplate'])->name('templates.save');
            Route::delete('/templates/{template}', [PrescriptionWebController::class, 'deleteTemplate'])->name('templates.delete');
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // AI DOCUMENTATION ASSISTANT - owner, doctor
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor'])->group(function () {
        Route::prefix('ai-assistant')->name('ai.')->group(function () {
            Route::get('/', [AiDocumentationController::class, 'index'])->name('index');
            Route::post('/transcribe', [AiDocumentationController::class, 'transcribe'])->name('transcribe');
            Route::post('/generate-notes', [AiDocumentationController::class, 'generateNotes'])->name('generate-notes');
            Route::post('/extract-codes', [AiDocumentationController::class, 'extractCodes'])->name('extract-codes');
            Route::post('/visit/{visit}/save', [AiDocumentationController::class, 'saveToVisit'])->name('save-to-visit');
            Route::post('/generate-letter', [AiDocumentationController::class, 'generateLetter'])->name('generate-letter');
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // LAB INTEGRATION - owner, doctor
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor'])->group(function () {
        Route::prefix('lab')->name('lab.')->group(function () {
            Route::get('/', [LabIntegrationController::class, 'index'])->name('index');
            Route::get('/tests/{provider}', [LabIntegrationController::class, 'getTestCatalog'])->name('tests');
            Route::post('/orders', [LabIntegrationController::class, 'createOrder'])->name('orders.create');
            Route::get('/orders/{order}', [LabIntegrationController::class, 'getOrderStatus'])->name('orders.status');
            Route::get('/orders/{order}/download', [LabIntegrationController::class, 'downloadResult'])->name('orders.download');
            Route::post('/sync/thyrocare', [LabIntegrationController::class, 'syncThyrocare'])->name('sync.thyrocare');
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // INSURANCE / TPA BILLING - owner, doctor, receptionist
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware(['role:doctor,receptionist'])->group(function () {
        Route::prefix('insurance')->name('insurance.')->group(function () {
            Route::get('/', [InsuranceController::class, 'index'])->name('index');
            Route::get('/tpas', [InsuranceController::class, 'getTPAs'])->name('tpas');
            Route::get('/patient/{patient}/preauth', [InsuranceController::class, 'createPreAuth'])->name('preauth.create');
            Route::post('/preauth', [InsuranceController::class, 'submitPreAuth'])->name('preauth.submit');
            Route::post('/claims', [InsuranceController::class, 'submitClaim'])->name('claims.submit');
            Route::post('/claims/{claim}/status', [InsuranceController::class, 'updateClaimStatus'])->name('claims.status');
            Route::post('/patient/{patient}/insurance', [InsuranceController::class, 'savePatientInsurance'])->name('patient.save');
            Route::get('/claims/{claim}/form', [InsuranceController::class, 'generateClaimForm'])->name('claims.form');
            Route::post('/tpa-config', [InsuranceController::class, 'storeTpaConfig'])->name('tpa.store');
            Route::delete('/tpa-config/{config}', [InsuranceController::class, 'destroyTpaConfig'])->name('tpa.destroy');
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // CLINIC USERS / STAFF MANAGEMENT - owner ONLY
    // ═══════════════════════════════════════════════════════════════════════
    Route::prefix('users')->name('clinic.users.')->middleware('role:owner')->group(function () {
        Route::get('/', [ClinicUserController::class, 'index'])->name('index');
        Route::get('/create', [ClinicUserController::class, 'create'])->name('create');
        Route::post('/', [ClinicUserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [ClinicUserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [ClinicUserController::class, 'update'])->name('update');
        Route::post('/{user}/toggle-status', [ClinicUserController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{user}', [ClinicUserController::class, 'destroy'])->name('destroy');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // MULTI-LOCATION MANAGEMENT - owner ONLY
    // ═══════════════════════════════════════════════════════════════════════
    Route::prefix('locations')->name('locations.')->middleware('role:owner')->group(function () {
        Route::get('/', [MultiLocationController::class, 'index'])->name('index');
        Route::post('/', [MultiLocationController::class, 'store'])->name('store');
        Route::put('/{location}', [MultiLocationController::class, 'update'])->name('update');
        Route::delete('/{location}', [MultiLocationController::class, 'destroy'])->name('destroy');
        Route::get('/{location}/rooms', [MultiLocationController::class, 'getRooms'])->name('rooms');
        Route::get('/analytics', [MultiLocationController::class, 'analytics'])->name('analytics');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // SUBSCRIPTION MANAGEMENT - owner ONLY
    // ═══════════════════════════════════════════════════════════════════════
    Route::middleware('role:owner')->group(function () {
        Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
        Route::post('/subscription', [SubscriptionController::class, 'create'])->name('subscription.create');
        Route::delete('/subscription/{subscription}', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // IPD MANAGEMENT
    // ═══════════════════════════════════════════════════════════════════════
    Route::prefix('ipd')->name('ipd.')->middleware(['role:doctor,nurse', 'hims:ipd'])->group(function () {
        Route::get('/', [IpdController::class, 'index'])->name('index');
        Route::get('/bed-map', [IpdController::class, 'bedMap'])->name('bed-map');
        Route::get('/admit', [IpdController::class, 'create'])->name('create');
        Route::post('/admit', [IpdController::class, 'store'])->name('store');
        Route::get('/{admission}', [IpdController::class, 'show'])->name('show');
        Route::post('/{admission}/discharge', [IpdController::class, 'discharge'])->name('discharge');
        Route::post('/{admission}/vitals', [IpdController::class, 'recordVitals'])->name('vitals.store');
        Route::post('/{admission}/progress-notes', [IpdController::class, 'addProgressNote'])->name('progress-notes.store');
        Route::get('/{admission}/print-card', [IpdController::class, 'printCard'])->name('print-card');
        Route::get('/{admission}/print-prescription', [IpdController::class, 'printPrescription'])->name('print-prescription');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // PHARMACY MANAGEMENT
    // ═══════════════════════════════════════════════════════════════════════
    Route::prefix('pharmacy')->name('pharmacy.')->middleware(['role:doctor,pharmacist', 'hims:pharmacy_inventory'])->group(function () {
        Route::get('/', [PharmacyController::class, 'index'])->name('index');
        Route::get('/portal', [PharmacyController::class, 'pharmacistPortal'])->name('portal');
        Route::get('/inventory', [PharmacyController::class, 'inventory'])->name('inventory');
        Route::get('/items/create', [PharmacyController::class, 'addItem'])->name('items.create');
        Route::post('/items', [PharmacyController::class, 'addItem'])->name('items.store');
        Route::post('/stock-in', [PharmacyController::class, 'stockIn'])->name('stock.in');
        Route::get('/dispense', [PharmacyController::class, 'dispensingForm'])->name('dispense.form');
        Route::post('/dispense', [PharmacyController::class, 'dispense'])->name('dispense');
        Route::get('/history', [PharmacyController::class, 'dispensingHistory'])->name('history');
        Route::get('/reports', [PharmacyController::class, 'stockReport'])->name('reports');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // LAB MANAGEMENT (Internal LIS)
    // ═══════════════════════════════════════════════════════════════════════
    Route::prefix('laboratory')->name('laboratory.')->middleware(['role:doctor,lab_technician', 'hims:lis_collection'])->group(function () {
        Route::get('/', [LabController::class, 'dashboard'])->name('index');
        Route::get('/catalog', [LabController::class, 'catalog'])->name('catalog');
        Route::post('/catalog', [LabController::class, 'storeTest'])->name('catalog.store');
        Route::get('/orders', [LabController::class, 'orders'])->name('orders');
        Route::post('/orders', [LabController::class, 'storeOrder'])->name('orders.store');
        Route::get('/orders/{orderId}/results', [LabController::class, 'resultEntry'])->name('result-entry');
        Route::post('/orders/{orderId}/results', [LabController::class, 'saveResult'])->name('save-result');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // LAB TECHNICIAN PORTAL
    // ═══════════════════════════════════════════════════════════════════════
    Route::prefix('lab-portal')->name('lab.technician.')->middleware(['role:lab_technician', 'hims:lis_collection'])->group(function () {
        Route::get('/', [LabTechnicianController::class, 'dashboard'])->name('dashboard');
        Route::post('/{orderId}/collect', [LabTechnicianController::class, 'collectSample'])->name('collect');
        Route::get('/{orderId}/results', [LabTechnicianController::class, 'resultForm'])->name('result-form');
        Route::post('/{orderId}/results', [LabTechnicianController::class, 'saveResults'])->name('save-results');
        Route::get('/doctor-results', [LabTechnicianController::class, 'doctorResults'])->name('doctor-results');
        Route::get('/{orderId}/report', [LabTechnicianController::class, 'viewReport'])->name('report');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // OPD QUEUE MANAGEMENT
    // ═══════════════════════════════════════════════════════════════════════
    Route::prefix('opd')->name('opd.')->middleware(['role:doctor,receptionist,nurse', 'hims:opd_hospital'])->group(function () {
        Route::get('/queue', [OpdController::class, 'queue'])->name('queue');
        Route::post('/walkin', [OpdController::class, 'walkin'])->name('walkin');
        Route::post('/{appointment}/status', [OpdController::class, 'updateStatus'])->name('status');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // AUDIT LOG - owner ONLY
    // ═══════════════════════════════════════════════════════════════════════
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index')->middleware('role:owner');

    // ═══════════════════════════════════════════════════════════════════════
    // WHATSAPP SETTINGS
    // ═══════════════════════════════════════════════════════════════════════
    Route::prefix('whatsapp-settings')->name('whatsapp-settings.')->middleware('role:owner')->group(function () {
        Route::get('/', [WhatsAppSettingsController::class, 'index'])->name('index');
        Route::post('/credentials', [WhatsAppSettingsController::class, 'saveCredentials'])->name('save-credentials');
        Route::post('/test', [WhatsAppSettingsController::class, 'testConnection'])->name('test');
        Route::post('/seed-templates', [WhatsAppSettingsController::class, 'seedTemplates'])->name('seed-templates');
        Route::post('/toggle-reminder', [WhatsAppSettingsController::class, 'toggleReminder'])->name('toggle-reminder');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // HOSPITAL SETTINGS
    // ═══════════════════════════════════════════════════════════════════════
    Route::prefix('hospital-settings')->name('hospital-settings.')->middleware('role:owner')->group(function () {
        Route::get('/', [HospitalSettingsController::class, 'index'])->name('index');
        Route::post('/', [HospitalSettingsController::class, 'update'])->name('update');
        Route::post('/wards', [HospitalSettingsController::class, 'storeWard'])->name('ward.store');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // CUSTOM EMR BUILDER - owner ONLY
    // ═══════════════════════════════════════════════════════════════════════
    Route::prefix('emr-builder')->name('emr-builder.')->middleware('role:owner')->group(function () {
        Route::get('/', [CustomEmrBuilderController::class, 'index'])->name('index');
        Route::get('/templates', [CustomEmrBuilderController::class, 'getTemplates'])->name('templates');
        Route::get('/templates/{template}', [CustomEmrBuilderController::class, 'getTemplate'])->name('templates.show');
        Route::post('/templates', [CustomEmrBuilderController::class, 'createTemplate'])->name('templates.store');
        Route::put('/templates/{template}', [CustomEmrBuilderController::class, 'updateTemplate'])->name('templates.update');
        Route::delete('/templates/{template}', [CustomEmrBuilderController::class, 'deleteTemplate'])->name('templates.destroy');
        Route::post('/templates/{template}/duplicate', [CustomEmrBuilderController::class, 'duplicateTemplate'])->name('templates.duplicate');
        Route::get('/templates/{template}/export', [CustomEmrBuilderController::class, 'exportTemplate'])->name('templates.export');
        Route::post('/import', [CustomEmrBuilderController::class, 'importTemplate'])->name('import');
        Route::get('/field-types', [CustomEmrBuilderController::class, 'getFieldTypes'])->name('field-types');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// PUBLIC BOOKING ROUTES (No auth required)
// ═══════════════════════════════════════════════════════════════════════════
Route::prefix('book')->name('public.booking.')->group(function () {
    Route::get('/', [PublicBookingController::class, 'directory'])->name('directory');
    Route::get('/{clinicSlug}/pre-visit/{token}', [PublicBookingController::class, 'showPreVisit'])->name('pre-visit');
    Route::post('/{clinicSlug}/pre-visit/{token}', [PublicBookingController::class, 'submitPreVisit'])->name('pre-visit.submit');
    Route::get('/{clinicSlug}/slots', [PublicBookingController::class, 'getAvailableSlots'])->name('slots');
    Route::post('/{clinicSlug}/create-order', [PublicBookingController::class, 'createPaymentOrder'])->name('create-order');
    Route::post('/verify-payment', [PublicBookingController::class, 'verifyPayment'])->name('verify-payment');
    Route::get('/{clinicSlug}', [PublicBookingController::class, 'show'])->name('show');
    Route::post('/{clinicSlug}', [PublicBookingController::class, 'book'])->name('book');
});

// ═══════════════════════════════════════════════════════════════════════════
// RAZORPAY WEBHOOK (No auth required)
// ═══════════════════════════════════════════════════════════════════════════
Route::post('/webhooks/razorpay', [PaymentWebController::class, 'handleWebhook'])->name('webhooks.razorpay');

// ═══════════════════════════════════════════════════════════════════════════
// RAZORPAY SUBSCRIPTION WEBHOOK (No auth required)
// ═══════════════════════════════════════════════════════════════════════════
Route::post('/subscription/webhook', [SubscriptionController::class, 'webhook'])->name('subscription.webhook');
