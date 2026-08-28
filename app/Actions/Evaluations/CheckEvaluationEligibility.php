<?php

namespace App\Actions\Evaluations;

use App\Models\Enrollment;

class CheckEvaluationEligibility
{
    /**
     * Validar si una matrícula cumple los requisitos para presentar una
     * evaluación (sección 20 del plan). Devuelve la lista de requisitos
     * faltantes; un arreglo vacío significa que está apto.
     *
     * @return array<int, string>
     */
    public function handle(Enrollment $enrollment): array
    {
        $missing = [];

        if ($enrollment->accumulated_hours < $enrollment->required_hours) {
            $pending = $enrollment->required_hours - $enrollment->accumulated_hours;
            $missing[] = "{$pending} horas académicas";
        }

        if (! in_array($enrollment->status, ['activa', 'en_recuperacion', 'extendida'], strict: true)) {
            $missing[] = 'Matrícula activa';
        }

        if ($enrollment->level->status !== 'activo') {
            $missing[] = 'Curso/nivel vigente';
        }

        return $missing;
    }
}
