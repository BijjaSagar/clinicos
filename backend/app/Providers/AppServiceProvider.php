<?php

namespace App\Providers;

use App\Models\Bed;
use App\Models\IpdAdmission;
use App\Models\PharmacyDispensing;
use App\Models\PharmacyStock;
use App\Observers\AuditableObserver;
use Illuminate\Database\Eloquent\Model;
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

        // Audit logging for clinical models
        IpdAdmission::observe(AuditableObserver::class);
        Bed::observe(AuditableObserver::class);
        PharmacyDispensing::observe(AuditableObserver::class);
        PharmacyStock::observe(AuditableObserver::class);
    }
}
