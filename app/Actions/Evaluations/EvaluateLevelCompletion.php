<?php

namespace App\Actions\Evaluations;

use App\Actions\Notifications\NotifyStudent;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\EvaluationResult;
use App\Models\RecoverySetting;
use App\Notifications\LevelApprovedNotification;
use App\Notifications\RecoveryPendingNotification;
use Illuminate\Support\Collection;

class EvaluateLevelCompletion
{
    public function __construct(
        protected NotifyStudent $notifyStudent,
        protected PromoteToNextLevel $promoteToNextLevel,
    ) {}

    /**
     * Después de registrar un resultado, revisa si la matrícula ya presentó
     * todas las evaluaciones activas del nivel y decide su siguiente estado
     * (sección 21 del plan): "aprobada" si todas están aprobadas y las horas
     * están completas, o "en_recuperacion" si perdió una o más.
     */
    public function handle(Enrollment $enrollment): void
    {
        if (! in_array($enrollment->status, ['activa', 'en_recuperacion', 'extendida'], strict: true)) {
            return;
        }

        $previousStatus = $enrollment->status;

        $evaluations = $enrollment->level->evaluations()->where('status', 'activo')->get();

        if ($evaluations->isEmpty()) {
            return;
        }

        $latestResults = $evaluations->map(
            fn ($evaluation) => $enrollment->evaluationResults()
                ->where('evaluation_id', $evaluation->id)
                ->orderByDesc('attempt_number')
                ->first()
        );

        if ($latestResults->contains(null)) {
            return;
        }

        $allApproved = $latestResults->every(fn ($result) => $result->result === 'aprobado');
        $hoursComplete = $enrollment->accumulated_hours >= $enrollment->required_hours;

        if ($allApproved && $hoursComplete) {
            $enrollment->update(['status' => 'aprobada', 'actual_end_date' => now()->toDateString()]);
            $this->notifyStudent->handle($enrollment->student, new LevelApprovedNotification($enrollment));
            $this->promoteToNextLevel->handle($enrollment);

            return;
        }

        if (! $allApproved) {
            $enrollment->update(['status' => 'en_recuperacion']);

            if ($previousStatus !== 'en_recuperacion') {
                $this->notifyFailedEvaluations($enrollment, $evaluations, $latestResults);
            }
        }
    }

    /**
     * Después de sumar horas por asistencia: si el estudiante ya había
     * aprobado todas las evaluaciones y con esto completa las horas del
     * nivel, la matrícula queda aprobada (y puede descargar su certificado).
     * A diferencia de handle(), nunca pasa la matrícula a recuperación.
     */
    public function approveIfComplete(Enrollment $enrollment): bool
    {
        if (! in_array($enrollment->status, ['activa', 'en_recuperacion', 'extendida'], strict: true)) {
            return false;
        }

        if ($enrollment->accumulated_hours < $enrollment->required_hours) {
            return false;
        }

        $evaluations = $enrollment->level->evaluations()->where('status', 'activo')->get();

        if ($evaluations->isEmpty()) {
            return false;
        }

        $allApproved = $evaluations->every(
            fn ($evaluation) => $enrollment->evaluationResults()
                ->where('evaluation_id', $evaluation->id)
                ->orderByDesc('attempt_number')
                ->first()?->result === 'aprobado'
        );

        if (! $allApproved) {
            return false;
        }

        $enrollment->update(['status' => 'aprobada', 'actual_end_date' => now()->toDateString()]);
        $this->notifyStudent->handle($enrollment->student, new LevelApprovedNotification($enrollment));
        $this->promoteToNextLevel->handle($enrollment);

        return true;
    }

    /**
     * @param  Collection<int, Evaluation>  $evaluations
     * @param  Collection<int, EvaluationResult>  $latestResults
     */
    protected function notifyFailedEvaluations($enrollment, $evaluations, $latestResults): void
    {
        $recoveryPeriodDays = RecoverySetting::current()->recovery_period_days;

        foreach ($latestResults as $index => $result) {
            if ($result->result !== 'reprobado') {
                continue;
            }

            $deadline = $result->evaluated_at->copy()->addDays($recoveryPeriodDays);

            $this->notifyStudent->handle(
                $enrollment->student,
                new RecoveryPendingNotification($evaluations[$index], $deadline),
            );
        }
    }
}
