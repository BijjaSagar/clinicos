<?php

namespace App\Providers;

use App\Models\Bed;
use App\Models\IpdAdmission;
use App\Models\PharmacyDispensing;
use App\Models\PharmacyStock;
use App\Observers\AuditableObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent N+1 queries in non-production environments.
        Model::preventLazyLoading(! app()->isProduction());

        // Prevent silently discarding attributes not in $fillable.
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        // Rate limiting for auth routes
        RateLimiter::for('auth', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Rate limiting for API
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiting for webhooks
        RateLimiter::for('webhooks', function ($request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        // Audit logging for clinical models
        IpdAdmission::observe(AuditableObserver::class);
        Bed::observe(AuditableObserver::class);
        PharmacyDispensing::observe(AuditableObserver::class);
        PharmacyStock::observe(AuditableObserver::class);
    }
}
