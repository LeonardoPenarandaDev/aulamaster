<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\EvaluationResult;
use App\Models\Extension;
use App\Models\InstitutionSetting;
use App\Models\Payment;
use App\Models\PaymentAgreement;
use App\Models\PaymentFollowUp;
use App\Models\Promotion;
use App\Models\Referral;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        // Apariencia de la aplicación (colores y menú): solo el administrador.
        Gate::define('update-appearance', fn (User $user) => $user->hasRole('admin'));

        // Nombre y logo de la institución para el <title> y el favicon de
        // la vista raíz de Inertia (parte 1 del plan de mejoras).
        View::composer('app', fn ($view) => $view->with('institution', InstitutionSetting::branding()));

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
            PaymentAgreement::class,
            PaymentFollowUp::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
