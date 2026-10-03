<?php

namespace App\Actions\Evaluations;

use App\Actions\Notifications\NotifyStudent;
use App\Actions\Pricing\CalculateEnrollmentPrice;
use App\Models\Enrollment;
use App\Notifications\NextLevelEnrollmentNotification;
use Illuminate\Support\Facades\DB;

class PromoteToNextLevel
{
    public function __construct(
        protected CalculateEnrollmentPrice $calculatePrice,
        protected NotifyStudent $notifyStudent,
    ) {}

    /**
     * Al aprobar un nivel, matricula al estudiante en el siguiente de la
     * ruta como "pendiente", con el precio del nivel y la matrícula
     * anterior enlazada (parte 5 del plan de mejoras). Devuelve null si el
     * nivel no tiene siguiente o si esa matrícula ya existe.
     */
    public function handle(Enrollment $enrollment): ?Enrollment
    {
        if ($enrollment->status !== 'aprobada') {
            return null;
        }

        $enrollment->loadMissing(['level.nextLevel', 'student']);
        $nextLevel = $enrollment->level->nextLevel;

        if (! $nextLevel || $this->alreadyEnrolled($enrollment, $nextLevel->id)) {
            return null;
        }

        $pricing = $this->calculatePrice->handle($nextLevel, $enrollment->student, null, null);
        $startDate = now();

        $nextEnrollment = DB::transaction(fn () => Enrollment::create([
            'student_id' => $enrollment->student_id,
            'level_id' => $nextLevel->id,
            'previous_enrollment_id' => $enrollment->id,
            'enrolled_at' => $startDate->toDateString(),
            'start_date' => $startDate->toDateString(),
            'estimated_end_date' => $nextLevel->duration_months
                ? $startDate->copy()->addMonths($nextLevel->duration_months)->toDateString()
                : null,
            'status' => 'pendiente',
            'required_hours' => $nextLevel->required_hours,
            'weekly_hours' => $nextLevel->weekly_hours ?: $enrollment->weekly_hours,
            'monthly_fee' => $nextLevel->monthly_fee,
            ...$pricing,
        ]));

        $this->notifyStudent->handle($enrollment->student, new NextLevelEnrollmentNotification($nextEnrollment));

        return $nextEnrollment;
    }

    protected function alreadyEnrolled(Enrollment $enrollment, int $nextLevelId): bool
    {
        return Enrollment::query()
            ->where('student_id', $enrollment->student_id)
            ->where('status', '!=', 'cancelada')
            ->where(fn ($query) => $query
                ->where('previous_enrollment_id', $enrollment->id)
                ->orWhere('level_id', $nextLevelId)
            )
            ->exists();
    }
}
