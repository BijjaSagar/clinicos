<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminClinicController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use App\Http\Controllers\Admin\AdminWhatsAppController;

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
| All routes prefixed with /admin
| Protected by super_admin middleware
*/

// Admin Auth (public)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
});

// Stop impersonation (accessible from anywhere when impersonating)
Route::get('/admin/stop-impersonating', [AdminAuthController::class, 'stopImpersonating'])
    ->middleware('auth')
    ->name('admin.stop-impersonating');

// Protected Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'super_admin'])->group(function () {
    
    // Logout
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    
    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard.index');
    
    // Clinics Management
    Route::prefix('clinics')->name('clinics.')->group(function () {
        Route::get('/', [AdminClinicController::class, 'index'])->name('index');
        Route::get('/create', [AdminClinicController::class, 'create'])->name('create');
        Route::post('/', [AdminClinicController::class, 'store'])->name('store');
        Route::get('/{clinic}', [AdminClinicController::class, 'show'])->name('show');
        Route::get('/{clinic}/edit', [AdminClinicController::class, 'edit'])->name('edit');
        Route::put('/{clinic}', [AdminClinicController::class, 'update'])->name('update');
        Route::delete('/{clinic}', [AdminClinicController::class, 'destroy'])->name('destroy');
        Route::post('/{clinic}/toggle-status', [AdminClinicController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{clinic}/extend-trial', [AdminClinicController::class, 'extendTrial'])->name('extend-trial');
        Route::post('/{clinic}/impersonate', [AdminClinicController::class, 'impersonate'])->name('impersonate');
    });
    
    // Users Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('/create', [AdminUserController::class, 'create'])->name('create');
        Route::post('/', [AdminUserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [AdminUserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // Subscriptions & Plans
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [AdminSubscriptionController::class, 'index'])->name('index');
        Route::post('/{clinic}/update-plan', [AdminSubscriptionController::class, 'updatePlan'])->name('update-plan');
        Route::post('/{clinic}/extend-trial', [AdminSubscriptionController::class, 'extendTrial'])->name('extend-trial');
    });
    
    // Settings
    Route::get('/settings', function () {
        return view('admin.settings.index');
    })->name('settings.index');

    // WhatsApp Global Credentials
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('/', [AdminWhatsAppController::class, 'index'])->name('index');
        Route::post('/save', [AdminWhatsAppController::class, 'save'])->name('save');
        Route::post('/test', [AdminWhatsAppController::class, 'test'])->name('test');
    });
});
