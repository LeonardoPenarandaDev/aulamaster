<?php

namespace App\Actions\Recovery;

use App\Models\Enrollment;
use App\Models\RecoverySetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GetPendingRecoveries
{
    /**
     * Lista, para cada matrícula en recuperación, las evaluaciones cuyo
     * último intento fue reprobado, con la fecha límite para presentar la
     * recuperación (sección 25 del plan) y los términos del próximo intento.
     *
     * @return Collection<int, array{enrollment: Enrollment, evaluation: mixed, last_attempt: mixed, deadline: Carbon, overdue: bool, terms: array}>
     */
    public function handle(): Collection
    {
        $periodDays = RecoverySetting::current()->recovery_period_days;
        $determineTerms = app(DetermineRecoveryTerms::class);

        $pending = collect();

        Enrollment::query()
            ->where('status', 'en_recuperacion')
            ->with(['student', 'level.course', 'level.evaluations' => fn ($q) => $q->where('status', 'activo')])
            ->get()
            ->each(function (Enrollment $enrollment) use (&$pending, $determineTerms, $periodDays) {
                foreach ($enrollment->level->evaluations as $evaluation) {
                    $lastAttempt = $enrollment->evaluationResults()
                        ->where('evaluation_id', $evaluation->id)
                        ->orderByDesc('attempt_number')
                        ->first();

                    if (! $lastAttempt || $lastAttempt->result !== 'reprobado') {
                        continue;
                    }

                    $deadline = $lastAttempt->evaluated_at->copy()->addDays($periodDays);

                    $pending->push([
                        'enrollment' => $enrollment,
                        'evaluation' => $evaluation,
                        'last_attempt' => $lastAttempt,
                        'deadline' => $deadline,
                        'overdue' => $deadline->isPast(),
                        'terms' => $determineTerms->handle($evaluation, $enrollment),
                    ]);
                }
            });

        return $pending;
    }
}
