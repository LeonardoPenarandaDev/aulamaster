<?php

namespace App\Actions\Dashboard;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;

class GetAdminDashboardStats
{
    /**
     * Indicadores del dashboard administrativo (sección 35 del plan).
     *
     * @return array<string, int|float>
     */
    public function handle(): array
    {
        return [
            'active_students' => Student::where('status', 'activo')->count(),
            'active_courses' => Course::where('status', 'activo')->count(),
            'teachers' => Teacher::where('status', 'activo')->count(),
            'classrooms' => Classroom::count(),
            'classes_today' => ClassSession::whereDate('date', today())->count(),
            'attendances_today' => Attendance::whereDate('class_date', today())->count(),
            'pending_evaluations' => $this->countPendingEvaluations(),
            'students_in_recovery' => Enrollment::where('status', 'en_recuperacion')->distinct('student_id')->count('student_id'),
            'students_with_overdue_payments' => Payment::where('status', 'vencido')->distinct('student_id')->count('student_id'),
            'income_this_month' => (float) Payment::where('status', 'pagado')
                ->whereBetween('paid_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->sum('final_amount'),
        ];
    }

    /**
     * Evaluaciones activas de un nivel que un estudiante apto todavía no ha presentado.
     */
    protected function countPendingEvaluations(): int
    {
        $enrollments = Enrollment::query()
            ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
            ->whereColumn('accumulated_hours', '>=', 'required_hours')
            ->with('level.evaluations')
            ->get();

        $pending = 0;

        foreach ($enrollments as $enrollment) {
            $presentedEvaluationIds = $enrollment->evaluationResults()->pluck('evaluation_id')->unique();

            $pending += $enrollment->level->evaluations
                ->where('status', 'activo')
                ->whereNotIn('id', $presentedEvaluationIds)
                ->count();
        }

        return $pending;
    }
}
