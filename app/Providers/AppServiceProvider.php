<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\EvaluationResult;
use App\Models\Extension;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Referral;
use App\Observers\AuditObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // Sección 46 del plan: HTTPS obligatorio en producción. Se fuerza el
        // esquema de las URLs generadas (route(), asset(), etc.) en vez de
        // depender solo de la configuración del servidor/proxy.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        foreach ([
            Attendance::class,
            AttendanceCorrection::class,
            EvaluationResult::class,
            Payment::class,
            Extension::class,
            Enrollment::class,
            Promotion::class,
            Referral::class,
            ClassSession::class,
            ClassSchedule::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
