<?php

namespace App\Actions\Recovery;

use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\EvaluationResult;
use App\Models\RecoverySetting;

class DetermineRecoveryTerms
{
    /**
     * Calcula los términos del próximo intento de una evaluación para una
     * matrícula: número de intento, si es recuperación, costo y si está
     * bloqueado (ya aprobó, o superó el máximo de recuperaciones gratuitas +
     * pagadas configurado — sección 22 del plan).
     *
     * @return array{attempt_number: int, is_recovery: bool, cost: float, blocked: bool, block_reason: ?string}
     */
    public function handle(Evaluation $evaluation, Enrollment $enrollment): array
    {
        $existing = EvaluationResult::query()
            ->where('evaluation_id', $evaluation->id)
            ->where('enrollment_id', $enrollment->id)
            ->orderByDesc('attempt_number')
            ->get();

        $attemptNumber = $existing->count() + 1;
        $isRecovery = $attemptNumber > 1;

        if ($existing->isNotEmpty() && $existing->first()->result === 'aprobado') {
            return [
                'attempt_number' => $attemptNumber,
                'is_recovery' => $isRecovery,
                'cost' => 0,
                'blocked' => true,
                'block_reason' => 'El estudiante ya aprobó esta evaluación; no aplica una recuperación.',
            ];
        }

        if (! $isRecovery) {
            return [
                'attempt_number' => $attemptNumber,
                'is_recovery' => false,
                'cost' => 0,
                'blocked' => false,
                'block_reason' => null,
            ];
        }

        $settings = RecoverySetting::current();
        $recoveryIndex = $attemptNumber - 1;
        $maxRecoveryAttempts = $settings->free_attempts + $settings->max_paid_attempts;

        if ($recoveryIndex > $maxRecoveryAttempts) {
            return [
                'attempt_number' => $attemptNumber,
                'is_recovery' => true,
                'cost' => 0,
                'blocked' => true,
                'block_reason' => "Se alcanzó el máximo de recuperaciones para esta evaluación ({$settings->free_attempts} gratuita(s) + {$settings->max_paid_attempts} pagada(s)).",
            ];
        }

        return [
            'attempt_number' => $attemptNumber,
            'is_recovery' => true,
            'cost' => $recoveryIndex <= $settings->free_attempts ? 0 : (float) $settings->recovery_price,
            'blocked' => false,
            'block_reason' => null,
        ];
    }
}
