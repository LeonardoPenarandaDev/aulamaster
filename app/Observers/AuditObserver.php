<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\EvaluationResult;
use App\Models\Extension;
use App\Models\Payment;
use App\Models\PaymentAgreement;
use App\Models\PaymentFollowUp;
use App\Models\Promotion;
use App\Models\Referral;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Genera el rastro de auditoría de la sección 38 del plan (usuario, rol,
 * acción, módulo, registro afectado, IP, valores antes/después) para las
 * operaciones críticas listadas ahí: asistencia, evaluaciones, pagos,
 * recuperaciones, extensiones, matrículas, promociones, referidos y
 * programación de clases. Se registra en AppServiceProvider sobre esos
 * modelos concretos, no sobre toda la aplicación.
 */
class AuditObserver
{
    /**
     * @var array<class-string, string>
     */
    protected static array $modules = [
        Attendance::class => 'asistencia',
        AttendanceCorrection::class => 'asistencia',
        EvaluationResult::class => 'evaluaciones',
        Payment::class => 'pagos',
        Extension::class => 'extensiones',
        Enrollment::class => 'matriculas',
        Promotion::class => 'promociones',
        Referral::class => 'referidos',
        ClassSession::class => 'programacion_clases',
        ClassSchedule::class => 'programacion_clases',
        PaymentAgreement::class => 'cartera',
        PaymentFollowUp::class => 'cartera',
    ];

    public function created(Model $model): void
    {
        $this->record($model, 'creado', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changed = Arr::except($model->getChanges(), ['updated_at']);

        if ($changed === []) {
            return;
        }

        $this->record($model, 'actualizado', Arr::only($model->getOriginal(), array_keys($changed)), $changed);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'eliminado', $model->getOriginal(), null);
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    protected function record(Model $model, string $action, ?array $old, ?array $new): void
    {
        $user = Auth::user();
        $label = class_basename($model);

        AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'role' => $user?->getRoleNames()->first(),
            'action' => $action,
            'module' => static::$modules[$model::class] ?? Str::snake(Str::pluralStudly($label)),
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'description' => "{$label} #{$model->getKey()} {$action}",
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()?->ip(),
        ]);
    }
}
