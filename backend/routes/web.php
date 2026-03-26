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

// Landing page
Route::get('/', fn() => view('welcome'))->name('home');

// Auth routes (guest only)
Route::middleware('guest')->group(function () {
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

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Schedule (alias for appointments)
    Route::get('/schedule', [AppointmentWebController::class, 'index'])->name('schedule');

    // Patients
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

    // Appointments
    Route::prefix('appointments')->name('appointments.')->group(function () {
        Route::get('/', [AppointmentWebController::class, 'index'])->name('index');
        Route::get('/create', [AppointmentWebController::class, 'create'])->name('create');
        Route::post('/', [AppointmentWebController::class, 'store'])->name('store');
        Route::get('/{appointment}', [AppointmentWebController::class, 'show'])->name('show');
        Route::put('/{appointment}/status', [AppointmentWebController::class, 'updateStatus'])->name('status');
        Route::delete('/{appointment}', [AppointmentWebController::class, 'destroy'])->name('destroy');
    });

    // EMR
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
    });
    
    // Drug Search API (for EMR)
    Route::get('/api/drugs/search', [EmrWebController::class, 'searchDrugs'])->name('api.drugs.search');

    // ABDM
    Route::get('/abdm', function () {
        return view('abdm.index');
    })->name('abdm.index');

    // Billing / Invoices
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [BillingWebController::class, 'index'])->name('index');
        Route::get('/create', [BillingWebController::class, 'create'])->name('create');
        Route::post('/', [BillingWebController::class, 'store'])->name('store');
        Route::get('/{invoice}', [BillingWebController::class, 'show'])->name('show');
        Route::get('/{invoice}/pdf', [BillingWebController::class, 'pdf'])->name('pdf');
        Route::post('/{invoice}/send-whatsapp', [BillingWebController::class, 'sendWhatsApp'])->name('send-whatsapp');
        Route::post('/{invoice}/mark-paid', [BillingWebController::class, 'markPaid'])->name('mark-paid');
    });

    // Payments
    Route::get('/payments', [PaymentWebController::class, 'index'])->name('payments.index');

    // GST Reports
    Route::get('/gst-reports', [GstReportWebController::class, 'index'])->name('gst-reports.index');

    // WhatsApp
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('/', [WhatsAppWebController::class, 'index'])->name('index');
        Route::post('/send', [WhatsAppWebController::class, 'send'])->name('send');
        Route::post('/broadcast', [WhatsAppWebController::class, 'broadcast'])->name('broadcast');
    });

    // Analytics
    Route::get('/analytics', [AnalyticsWebController::class, 'index'])->name('analytics.index');

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsWebController::class, 'index'])->name('index');
        Route::post('/clinic', [SettingsWebController::class, 'updateClinic'])->name('clinic');
        Route::post('/billing', [SettingsWebController::class, 'updateBilling'])->name('billing');
    });

    // Vendor Portal (Lab Orders)
    Route::prefix('vendor')->name('vendor.')->group(function () {
        Route::get('/', [VendorWebController::class, 'index'])->name('index');
        Route::post('/orders/{order}/accept', [VendorWebController::class, 'acceptOrder'])->name('accept');
        Route::post('/orders/{order}/upload', [VendorWebController::class, 'uploadResult'])->name('upload');
    });

    // Photo Vault
    Route::get('/photo-vault', [PhotoVaultWebController::class, 'index'])->name('photo-vault.index');

    // Prescriptions
    Route::get('/prescriptions', [PrescriptionWebController::class, 'index'])->name('prescriptions.index');

    // Clinic Users (Staff Management)
    Route::prefix('users')->name('clinic.users.')->group(function () {
        Route::get('/', [ClinicUserController::class, 'index'])->name('index');
        Route::get('/create', [ClinicUserController::class, 'create'])->name('create');
        Route::post('/', [ClinicUserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [ClinicUserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [ClinicUserController::class, 'update'])->name('update');
        Route::post('/{user}/toggle-status', [ClinicUserController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{user}', [ClinicUserController::class, 'destroy'])->name('destroy');
    });
});
